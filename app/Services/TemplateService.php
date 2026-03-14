<?php

namespace App\Services;

use App\Models\EmailTemplate;
use Illuminate\Support\Str;

class TemplateService
{
    /**
     * Render a template with variables.
     *
     * @param string $slug
     * @param array $variables
     * @param string|null $locale
     * @return array
     */
    public function render(string $slug, array $variables, string $locale = null): array
    {
        $template = $this->getTemplateBySlug($slug, $locale);
        
        if (!$template) {
            throw new \InvalidArgumentException("Template with slug '{$slug}' not found.");
        }
        
        $this->validateVariables($template->getVariablesArray(), $variables);
        
        $subject = $this->replaceVariables($template->subject, $variables);
        $bodyHtml = $this->replaceVariables($template->body_html, $variables);
        $bodyText = $template->body_text ? $this->replaceVariables($template->body_text, $variables) : null;
        
        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'template' => $template,
        ];
    }

    /**
     * Create a new template.
     *
     * @param array $data
     * @return EmailTemplate
     */
    public function create(array $data): EmailTemplate
    {
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        
        if (isset($data['variables']) && is_array($data['variables'])) {
            $data['variables'] = json_encode($data['variables']);
        }
        
        return EmailTemplate::create($data);
    }

    /**
     * Update an existing template.
     *
     * @param string $slug
     * @param array $data
     * @return EmailTemplate
     */
    public function update(string $slug, array $data): EmailTemplate
    {
        $template = $this->getTemplateBySlug($slug);
        
        if (!$template) {
            throw new \InvalidArgumentException("Template with slug '{$slug}' not found.");
        }
        
        if (isset($data['variables']) && is_array($data['variables'])) {
            $data['variables'] = json_encode($data['variables']);
        }
        
        $template->update($data);
        
        return $template->fresh();
    }

    /**
     * Get template by slug.
     *
     * @param string $slug
     * @param string|null $locale
     * @return EmailTemplate|null
     */
    public function getTemplateBySlug(string $slug, string $locale = null): ?EmailTemplate
    {
        $query = EmailTemplate::where('slug', $slug)->active();
        
        if ($locale) {
            $query->where('locale', $locale);
        } else {
            $query->where('locale', 'en'); // Default locale
        }
        
        return $query->first();
    }

    /**
     * Get available variables for a template.
     *
     * @param string $slug
     * @return array
     */
    public function getVariables(string $slug): array
    {
        $template = $this->getTemplateBySlug($slug);
        
        if (!$template) {
            return [];
        }
        
        return $template->getVariablesArray();
    }

    /**
     * Validate that all required variables are provided.
     *
     * @param array $templateVariables
     * @param array $providedVariables
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validateVariables(array $templateVariables, array $providedVariables): bool
    {
        // In a real implementation, you might check required variables
        // For now, just return true
        return true;
    }

    /**
     * Replace variables in a string.
     *
     * @param string $content
     * @param array $variables
     * @return string
     */
    protected function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $content = str_replace($placeholder, $value, $content);
        }
        
        // Remove any unreplaced variables
        $content = preg_replace('/{{[^}]+}}/', '', $content);
        
        return $content;
    }

    /**
     * Get all templates.
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(array $filters = [])
    {
        $query = EmailTemplate::query();
        
        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        
        if (isset($filters['locale'])) {
            $query->where('locale', $filters['locale']);
        }
        
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        return $query->orderBy('name')->get();
    }

    /**
     * Duplicate a template.
     *
     * @param string $slug
     * @param array $overrides
     * @return EmailTemplate
     */
    public function duplicate(string $slug, array $overrides = []): EmailTemplate
    {
        $template = $this->getTemplateBySlug($slug);
        
        if (!$template) {
            throw new \InvalidArgumentException("Template with slug '{$slug}' not found.");
        }
        
        $newData = $template->toArray();
        unset($newData['id'], $newData['uuid'], $newData['created_at'], $newData['updated_at']);
        
        $newData = array_merge($newData, $overrides);
        
        if (!isset($newData['slug'])) {
            $newData['slug'] = $newData['slug'] ?? Str::slug($newData['name']) . '-' . Str::random(4);
        }
        
        if (!isset($newData['uuid'])) {
            $newData['uuid'] = (string) Str::uuid();
        }
        
        return $this->create($newData);
    }

    /**
     * Delete a template.
     *
     * @param string $slug
     * @return bool
     */
    public function delete(string $slug): bool
    {
        $template = $this->getTemplateBySlug($slug);
        
        if (!$template) {
            return false;
        }
        
        return $template->delete();
    }
}