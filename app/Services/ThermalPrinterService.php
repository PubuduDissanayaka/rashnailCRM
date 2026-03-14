<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Setting;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

class ThermalPrinterService
{
    protected $printer;
    protected $connector;

    /**
     * Initialize printer connection based on settings
     */
    public function __construct()
    {
        $this->connect();
    }

    /**
     * Connect to printer based on configuration
     */
    protected function connect()
    {
        $printerType = Setting::get('printer.type', 'windows');
        $printerName = Setting::get('printer.name', 'POS-80');
        $printerIp = Setting::get('printer.ip', '192.168.1.100');
        $printerPort = Setting::get('printer.port', 9100);
        $printerPath = Setting::get('printer.path', '/dev/usb/lp0');

        try {
            switch ($printerType) {
                case 'windows':
                    // Windows shared printer or USB printer
                    $this->connector = new WindowsPrintConnector($printerName);
                    break;

                case 'network':
                    // Network printer (most common for modern thermal printers)
                    $this->connector = new NetworkPrintConnector($printerIp, $printerPort);
                    break;

                case 'file':
                    // Linux/Unix file-based printer
                    $this->connector = new FilePrintConnector($printerPath);
                    break;

                default:
                    throw new \Exception("Unsupported printer type: {$printerType}");
            }

            $this->printer = new Printer($this->connector);
        } catch (\Exception $e) {
            \Log::error('Printer connection failed: ' . $e->getMessage());
            throw new \Exception('Could not connect to thermal printer: ' . $e->getMessage());
        }
    }

    /**
     * Print a receipt for a sale
     */
    public function printReceipt(Sale $sale)
    {
        if (!$this->printer) {
            throw new \Exception('Printer not connected');
        }

        try {
            // Get business settings
            $businessName = Setting::get('business.name', config('app.name'));
            $businessAddress = Setting::get('business.address', '');
            $businessPhone = Setting::get('business.phone', '');
            $businessEmail = Setting::get('business.email', '');
            $currencySymbol = Setting::get('payment.currency_symbol', '$');

            // Initialize
            $this->printer->initialize();
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);

            // Header
            $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
            $this->printer->text($businessName . "\n");
            $this->printer->selectPrintMode();

            if ($businessAddress) {
                $this->printer->text($businessAddress . "\n");
            }
            if ($businessPhone) {
                $this->printer->text("Phone: {$businessPhone}\n");
            }
            if ($businessEmail) {
                $this->printer->text("Email: {$businessEmail}\n");
            }

            // Separator
            $this->printer->text(str_repeat("-", 48) . "\n");

            // Sale info
            $this->printer->setJustification(Printer::JUSTIFY_LEFT);
            $this->printer->text("Receipt #: {$sale->sale_number}\n");
            $this->printer->text("Date: " . $sale->sale_date->format('M d, Y h:i A') . "\n");
            $this->printer->text("Cashier: {$sale->user->name}\n");

            if ($sale->customer) {
                $customerName = $sale->customer->first_name . ' ' . $sale->customer->last_name;
                $this->printer->text("Customer: {$customerName}\n");
            }

            // Separator
            $this->printer->text(str_repeat("-", 48) . "\n");

            // Items
            foreach ($sale->items as $item) {
                $itemName = $this->truncate($item->item_name, 30);
                $price = $currencySymbol . number_format($item->line_total, 2);

                $this->printer->text($itemName . "\n");

                $qty = "{$item->quantity} x {$currencySymbol}" . number_format($item->unit_price, 2);
                $padding = str_repeat(" ", 48 - strlen($qty) - strlen($price));
                $this->printer->text("  {$qty}{$padding}{$price}\n");
            }

            // Separator
            $this->printer->text(str_repeat("-", 48) . "\n");

            // Totals
            $this->printLine("Subtotal:", $currencySymbol . number_format($sale->subtotal, 2));

            if ($sale->discount_amount > 0) {
                $this->printLine("Discount:", "-" . $currencySymbol . number_format($sale->discount_amount, 2));
            }

            if ($sale->tax_amount > 0) {
                $this->printLine("Tax:", $currencySymbol . number_format($sale->tax_amount, 2));
            }

            // Grand total
            $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
            $this->printLine("TOTAL:", $currencySymbol . number_format($sale->total_amount, 2));
            $this->printer->selectPrintMode();

            // Separator
            $this->printer->text(str_repeat("-", 48) . "\n");

            // Payment
            foreach ($sale->payments as $payment) {
                $method = ucfirst(str_replace('_', ' ', $payment->payment_method));
                $this->printLine($method . ":", $currencySymbol . number_format($payment->amount, 2));
            }

            if ($sale->change_amount > 0) {
                $this->printer->selectPrintMode(Printer::MODE_EMPHASIZED);
                $this->printLine("Change:", $currencySymbol . number_format($sale->change_amount, 2));
                $this->printer->selectPrintMode();
            }

            // Footer
            $this->printer->text(str_repeat("-", 48) . "\n");
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->text("\nThank you for your business!\n");

            if ($sale->notes) {
                $this->printer->text("\n" . $sale->notes . "\n");
            }

            // Cut paper
            $this->printer->feed(3);
            $this->printer->cut();

            // Close connection
            $this->printer->close();

        } catch (\Exception $e) {
            if ($this->printer) {
                $this->printer->close();
            }
            throw $e;
        }
    }

    /**
     * Print a line with left and right alignment
     */
    protected function printLine($left, $right)
    {
        $width = 48;
        $padding = str_repeat(" ", $width - strlen($left) - strlen($right));
        $this->printer->text($left . $padding . $right . "\n");
    }

    /**
     * Truncate text to fit printer width
     */
    protected function truncate($text, $length)
    {
        return strlen($text) > $length ? substr($text, 0, $length - 3) . '...' : $text;
    }

    /**
     * Test printer connection
     */
    public function testPrinter()
    {
        try {
            $this->printer->initialize();
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
            $this->printer->text("PRINTER TEST\n");
            $this->printer->selectPrintMode();
            $this->printer->text("\nConnection successful!\n");
            $this->printer->text(date('Y-m-d H:i:s') . "\n");
            $this->printer->feed(3);
            $this->printer->cut();
            $this->printer->close();

            return true;
        } catch (\Exception $e) {
            if ($this->printer) {
                $this->printer->close();
            }
            throw $e;
        }
    }
}
