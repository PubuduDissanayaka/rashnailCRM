<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Services\TemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TemplateController extends Controller
{
    /**
     * The template service.
     *
     * @var TemplateService
     */
    protected $templateService;

    /**
     * Create a new controller instance.
     *
     * @param TemplateService $templateService
     */
    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
        $this->middleware('auth');
        $this->middleware('can:manage system');
    }

    /**
     * Display a listing of email templates.
     */
    public function index(Request $request)
    {
        $query = EmailTemplate::query();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by locale
        if ($request->has('locale')) {
            $query->where('locale', $request->locale);
        }

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Search by name or subject
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Sort
        $sort = $request->get('sort', 'name');
        $order = $request->get('order', 'asc');
        $query->orderBy($sort, $order);

        $perPage = $request->get('per_page', 20);
        $templates = $query->paginate($perPage);

        // Get template statistics
        $stats = [
            'total' => EmailTemplate::count(),
            'active' => EmailTemplate::where('is_active', true)->count(),
            'by_category' => EmailTemplate::selectRaw('category, count(*) as count')
                ->groupBy('category')
                ->get()
                ->pluck('count', 'category')
                ->toArray(),
            'by_locale' => EmailTemplate::selectRaw('locale, count(*) as count')
                ->groupBy('locale')
                ->get()
                ->pluck('count', 'locale')
                ->toArray(),
        ];

        // Get unique categories and locales for filters
        $categories = EmailTemplate::distinct('category')->pluck('category')->filter();
        $locales = EmailTemplate::distinct('locale')->pluck('locale')->filter();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $templates,
                'stats' => $stats,
                'filters' => [
                    'categories' => $categories,
                    'locales' => $locales,
                ],
            ]);
        }

        return view('notifications.templates.index', compact('templates', 'stats', 'categories', 'locales'));
    }

    /**
     * Show the form for creating a new email template.
     */
    public function create()
    {
        $categories = $this->getTemplateCategories();
        $locales = $this->getAvailableLocales();
        $variables = $this->getSystemVariables();

        return view('notifications.templates.create', compact('categories', 'locales', 'variables'));
    }

    /**
     * Store a newly created email template.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:email_templates,slug',
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'locale' => 'nullable|string|max:10',
            'is_active' => 'boolean',
            'variables' => 'nullable|array',
            'variables.*' => 'string|max:50',
        ]);

        $data = $request->all();
        
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        
        // Ensure slug is unique
        $counter = 1;
        $originalSlug = $data['slug'];
        while (EmailTemplate::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        // Set created_by and updated_by
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        
        // Create template
        $template = $this->templateService->create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Template created successfully',
                'data' => $template,
            ], 201);
        }

        return redirect()->route('notifications.templates.index')
            ->with('success', 'Template created successfully');
    }

    /**
     * Display the specified email template.
     */
    public function show(string $id)
    {
        $template = EmailTemplate::where('uuid', $id)->firstOrFail();
        
        // Get preview with sample data
        $sampleVariables = $this->getSampleVariables($template);
        $preview = null;
        
        try {
            $preview = $this->templateService->render($template->slug, $sampleVariables, $template->locale);
        } catch (\Exception $e) {
            // Preview generation failed, but we can still show the template
        }

        if (request()->expectsJson()) {
            return response()->json([
                'data' => $template,
                'preview' => $preview,
                'sample_variables' => $sampleVariables,
            ]);
        }

        return view('notifications.templates.show', compact('template', 'preview', 'sampleVariables'));
    }

    /**
     * Show the form for editing the specified email template.
     */
    public function edit(string $id)
    {
        $template = EmailTemplate::where('uuid', $id)->firstOrFail();
        $categories = $this->getTemplateCategories();
        $locales = $this->getAvailableLocales();
        $variables = $this->getSystemVariables();

        return view('notifications.templates.edit', compact('template', 'categories', 'locales', 'variables'));
    }

    /**
     * Update the specified email template.
     */
    public function update(Request $request, string $id)
    {
        $template = EmailTemplate::where('uuid', $id)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:email_templates,slug,' . $template->id,
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'locale' => 'nullable|string|max:10',
            'is_active' => 'boolean',
            'variables' => 'nullable|array',
            'variables.*' => 'string|max:50',
        ]);

        $data = $request->all();
        
        // Update slug if changed
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        
        // Ensure slug is unique (excluding current template)
        if ($data['slug'] !== $template->slug) {
            $counter = 1;
            $originalSlug = $data['slug'];
            while (EmailTemplate::where('slug', $data['slug'])->where('id', '!=', $template->id)->exists()) {
                $data['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }
        
        // Set updated_by
        $data['updated_by'] = Auth::id();
        
        // Update template
        $template = $this->templateService->update($template->slug, $data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Template updated successfully',
                'data' => $template,
            ]);
        }

        return redirect()->route('notifications.templates.index')
            ->with('success', 'Template updated successfully');
    }

    /**
     * Remove the specified email template.
     */
    public function destroy(string $id)
    {
        $template = EmailTemplate::where('uuid', $id)->firstOrFail();
        
        $deleted = $this->templateService->delete($template->slug);

        if (!$deleted) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to delete template',
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to delete template');
        }

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Template deleted successfully',
            ]);
        }

        return redirect()->route('notifications.templates.index')
            ->with('success', 'Template deleted successfully');
    }

    /**
     * Duplicate the specified email template.
     */
    public function duplicate(string $id)
    {
        $template = EmailTemplate::where('uuid', $id)->firstOrFail();
        
        $newTemplate = $this->templateService->duplicate($template->slug, [
            'name' => $template->name . ' (Copy)',
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Template duplicated successfully',
                'data' => $newTemplate,
            ]);
        }

        return redirect()->route('notifications.templates.edit', $newTemplate->uuid)
            ->with('success', 'Template duplicated successfully');
    }

    /**
     * Preview template with provided variables.
     */
    public function preview(Request $request, string $id = null)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'variables' => 'nullable|array',
            'variables.*' => 'string|max:50',
            'sample_data' => 'nullable|array',
        ]);

        $subject = $request->subject;
        $bodyHtml = $request->body_html;
        $bodyText = $request->body_text;
        $variables = $request->variables ?? [];
        $sampleData = $request->sample_data ?? [];

        // Replace variables in content
        $previewSubject = $this->replaceVariables($subject, $sampleData);
        $previewBodyHtml = $this->replaceVariables($bodyHtml, $sampleData);
        $previewBodyText = $bodyText ? $this->replaceVariables($bodyText, $sampleData) : null;

        if ($request->expectsJson()) {
            return response()->json([
                'preview' => [
                    'subject' => $previewSubject,
                    'body_html' => $previewBodyHtml,
                    'body_text' => $previewBodyText,
                ],
                'variables' => $variables,
                'sample_data' => $sampleData,
            ]);
        }

        return view('notifications.templates.partials.preview', [
            'subject' => $previewSubject,
            'body_html' => $previewBodyHtml,
            'body_text' => $previewBodyText,
        ]);
    }

    /**
     * Toggle template active status.
     */
    public function toggleActive(string $id)
    {
        $template = EmailTemplate::where('uuid', $id)->firstOrFail();
        $template->update(['is_active' => !$template->is_active]);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Template status updated',
                'is_active' => $template->is_active,
            ]);
        }

        return redirect()->back()->with('success', 'Template status updated');
    }

    /**
     * Get template categories.
     */
    private function getTemplateCategories()
    {
        return [
            'system' => 'System',
            'user' => 'User',
            'attendance' => 'Attendance',
            'appointment' => 'Appointment',
            'billing' => 'Billing',
            'marketing' => 'Marketing',
            'notification' => 'Notification',
            'other' => 'Other',
        ];
    }

    /**
     * Get available locales.
     */
    private function getAvailableLocales()
    {
        return [
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ru' => 'Russian',
            'ja' => 'Japanese',
            'zh' => 'Chinese',
            'ar' => 'Arabic',
        ];
    }

    /**
     * Get system variables.
     */
    private function getSystemVariables()
    {
        return [
            'user' => [
                'name' => 'User full name',
                'email' => 'User email address',
                'phone' => 'User phone number',
                'role' => 'User role',
            ],
            'appointment' => [
                'date' => 'Appointment date',
                'time' => 'Appointment time',
                'service' => 'Service name',
                'staff' => 'Staff member name',
                'location' => 'Location address',
            ],
            'attendance' => [
                'check_in_time' => 'Check-in time',
                'check_out_time' => 'Check-out time',
                'total_hours' => 'Total hours worked',
                'date' => 'Attendance date',
            ],
            'system' => [
                'company_name' => 'Company name',
                'company_email' => 'Company email',
                'company_phone' => 'Company phone',
                'current_date' => 'Current date',
                'current_time' => 'Current time',
                'website_url' => 'Website URL',
            ],
        ];
    }

    /**
     * Get sample variables for preview.
     */
    private function getSampleVariables(EmailTemplate $template)
    {
        $variables = $template->getVariablesArray();
        $sampleData = [];
        
        foreach ($variables as $variable) {
            // Generate sample data based on variable name
            if (str_contains($variable, 'name')) {
                $sampleData[$variable] = 'John Doe';
            } elseif (str_contains($variable, 'email')) {
                $sampleData[$variable] = 'john.doe@example.com';
            } elseif (str_contains($variable, 'date')) {
                $sampleData[$variable] = date('Y-m-d');
            } elseif (str_contains($variable, 'time')) {
                $sampleData[$variable] = date('H:i');
            } elseif (str_contains($variable, 'amount') || str_contains($variable, 'price')) {
                $sampleData[$variable] = '$99.99';
            } else {
                $sampleData[$variable] = 'Sample ' . ucfirst(str_replace('_', ' ', $variable));
            }
        }
        
        return $sampleData;
    }

    /**
     * Replace variables in a string.
     */
    private function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $content = str_replace($placeholder, $value, $content);
        }
        
        // Remove any unreplaced variables
        $content = preg_replace('/{{[^}]+}}/', '', $content);
        
        return $content;
    }
}
