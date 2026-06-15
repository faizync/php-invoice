<?php
// src/Invoice.php
// Invoice data model — validation + calculations

class Invoice
{
    public string $invoice_number;
    public string $invoice_date;
    public string $due_date;
    public string $from_name;
    public string $from_email;
    public string $from_address;
    public string $to_name;
    public string $to_email;
    public string $to_address;
    public array  $items;
    public string $notes;
    public float  $tax_rate;

    public function __construct(array $data)
    {
        $this->invoice_number = $data['invoice_number'] ?? '';
        $this->invoice_date   = $data['invoice_date']   ?? '';
        $this->due_date       = $data['due_date']       ?? '';
        $this->from_name      = $data['from_name']      ?? '';
        $this->from_email     = $data['from_email']     ?? '';
        $this->from_address   = $data['from_address']   ?? '';
        $this->to_name        = $data['to_name']        ?? '';
        $this->to_email       = $data['to_email']       ?? '';
        $this->to_address     = $data['to_address']     ?? '';
        $this->notes          = $data['notes']          ?? '';
        $this->tax_rate       = (float)($data['tax_rate'] ?? 0);

        // Filter out empty line items
        $this->items = array_values(array_filter(
            $data['items'] ?? [],
            fn($item) => !empty(trim($item['description'] ?? ''))
        ));
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->invoice_number))    $errors[] = 'Invoice number is required.';
        if (empty($this->invoice_date))      $errors[] = 'Invoice date is required.';
        if (empty($this->due_date))          $errors[] = 'Due date is required.';
        if (empty($this->from_name))         $errors[] = 'Your name / company is required.';
        if (empty($this->from_email))        $errors[] = 'Your email is required.';
        if (!filter_var($this->from_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Your email is invalid.';
        if (empty($this->to_name))           $errors[] = 'Client name is required.';
        if (empty($this->to_email))          $errors[] = 'Client email is required.';
        if (!filter_var($this->to_email, FILTER_VALIDATE_EMAIL))   $errors[] = 'Client email is invalid.';
        if (empty($this->items))             $errors[] = 'At least one line item with a description is required.';
        if ($this->tax_rate < 0 || $this->tax_rate > 100)          $errors[] = 'Tax rate must be between 0 and 100.';

        foreach ($this->items as $i => $item) {
            $n = $i + 1;
            if (empty(trim($item['description'] ?? ''))) $errors[] = "Item $n: description is required.";
            if (!isset($item['qty']) || (int)$item['qty'] < 1)     $errors[] = "Item $n: quantity must be at least 1.";
            if (!isset($item['price']) || (float)$item['price'] < 0) $errors[] = "Item $n: price must be 0 or more.";
        }

        return $errors;
    }

    public function subtotal(): float
    {
        return array_reduce($this->items, function (float $carry, array $item) {
            return $carry + ((float)($item['qty'] ?? 0) * (float)($item['price'] ?? 0));
        }, 0.0);
    }

    public function tax(): float
    {
        return $this->subtotal() * ($this->tax_rate / 100);
    }

    public function total(): float
    {
        return $this->subtotal() + $this->tax();
    }
}
