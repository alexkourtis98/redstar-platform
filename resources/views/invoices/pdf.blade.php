<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #1a202c;
        }

        .container {
            padding: 20px;
        }

        .header {
            margin-bottom: 30px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
        }

        .header-grid {
            display: table;
            width: 100%;
        }

        .header-left, .header-right {
            display: table-cell;
            vertical-align: top;
        }

        .header-left {
            width: 60%;
        }

        .header-right {
            width: 40%;
            text-align: right;
        }

        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 9pt;
            color: #4b5563;
            line-height: 1.5;
        }

        .invoice-title {
            font-size: 24pt;
            font-weight: bold;
            color: #1a202c;
            margin-bottom: 5px;
        }

        .invoice-meta {
            font-size: 9pt;
            color: #6b7280;
        }

        .parties {
            margin-bottom: 25px;
            display: table;
            width: 100%;
        }

        .party {
            display: table-cell;
            width: 50%;
            padding: 15px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }

        .party-title {
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .party-name {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .party-details {
            font-size: 9pt;
            color: #4b5563;
            line-height: 1.6;
        }

        .aade-mark {
            margin: 20px 0;
            padding: 15px;
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 4px;
            text-align: center;
        }

        .aade-mark-title {
            font-size: 9pt;
            font-weight: bold;
            color: #92400e;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .aade-mark-value {
            font-size: 14pt;
            font-weight: bold;
            color: #1a202c;
            font-family: monospace;
            letter-spacing: 2px;
        }

        .qr-code {
            margin-top: 10px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table thead {
            background: #2563eb;
            color: white;
        }

        .items-table th {
            padding: 10px;
            text-align: left;
            font-size: 9pt;
            font-weight: bold;
        }

        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10pt;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals {
            margin-left: auto;
            width: 300px;
        }

        .totals-row {
            display: table;
            width: 100%;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .totals-row.final {
            border-bottom: none;
            border-top: 2px solid #2563eb;
            background: #eff6ff;
            padding: 12px 0;
            font-weight: bold;
            font-size: 12pt;
        }

        .totals-label {
            display: table-cell;
            width: 60%;
            text-align: right;
            padding-right: 15px;
            color: #4b5563;
        }

        .totals-value {
            display: table-cell;
            width: 40%;
            text-align: right;
            font-weight: bold;
        }

        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-size: 8pt;
            color: #6b7280;
            line-height: 1.6;
        }

        .greek-legal {
            margin-top: 10px;
            font-size: 7pt;
            color: #9ca3af;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="header-grid">
                <div class="header-left">
                    <div class="company-name">{{ $entity->name }}</div>
                    <div class="company-details">
                        @if($entity->address){{ $entity->address }}<br>@endif
                        @if($entity->vat_number)ΑΦΜ: {{ $entity->vat_number }}<br>@endif
                        @if($entity->tax_office)ΔΟΥ: {{ $entity->tax_office }}<br>@endif
                        @if($entity->email)Email: {{ $entity->email }}<br>@endif
                        @if($entity->phone)Τηλ: {{ $entity->phone }}@endif
                    </div>
                </div>
                <div class="header-right">
                    <div class="invoice-title">ΤΙΜΟΛΟΓΙΟ</div>
                    <div class="invoice-meta">
                        <strong>Αρ. Τιμολογίου:</strong> {{ $invoice->invoice_number }}<br>
                        <strong>Ημερομηνία:</strong> {{ $invoice->issue_date->format('d/m/Y') }}<br>
                        <strong>Σειρά:</strong> {{ $invoice->series_code }}
                    </div>
                </div>
            </div>
        </div>

        {{-- AADE MARK (Prominently Displayed) --}}
        @if($invoice->mark)
        <div class="aade-mark">
            <div class="aade-mark-title">ΜΑΡΚ Παραστατικού (AADE)</div>
            <div class="aade-mark-value">{{ $invoice->mark }}</div>
            @if($invoice->qr_url)
            <div class="qr-code">
                <img src="{{ $invoice->qr_url }}" alt="QR Code" style="width: 120px; height: 120px;">
            </div>
            @endif
        </div>
        @endif

        {{-- Parties (Issuer & Customer) --}}
        <div class="parties">
            <div class="party" style="border-right: none;">
                <div class="party-title">Πελάτης</div>
                <div class="party-name">{{ $client->name }}</div>
                <div class="party-details">
                    @if($client->company_name && $client->company_name !== $client->name)
                        {{ $client->company_name }}<br>
                    @endif
                    @if($client->vat_number)
                        ΑΦΜ: {{ $client->vat_number }}<br>
                    @endif
                    @if($client->email)
                        Email: {{ $client->email }}<br>
                    @endif
                    @if($client->phone)
                        Τηλ: {{ $client->phone }}
                    @endif
                </div>
            </div>
            <div class="party">
                <div class="party-title">Έργο</div>
                @if($project)
                <div class="party-name">{{ $project->title }}</div>
                <div class="party-details">
                    @if($project->description)
                        {{ Str::limit($project->description, 150) }}<br>
                    @endif
                    @if($project->start_date)
                        Έναρξη: {{ $project->start_date->format('d/m/Y') }}<br>
                    @endif
                    @if($project->end_date)
                        Λήξη: {{ $project->end_date->format('d/m/Y') }}
                    @endif
                </div>
                @else
                <div class="party-details">Γενική πληρωμή</div>
                @endif
            </div>
        </div>

        {{-- Line Items --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Περιγραφή</th>
                    <th class="text-center" style="width: 10%;">Ποσότητα</th>
                    <th class="text-right" style="width: 15%;">Τιμή Μονάδας</th>
                    <th class="text-right" style="width: 10%;">ΦΠΑ %</th>
                    <th class="text-right" style="width: 15%;">Σύνολο</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $lineItems = is_string($invoice->line_items)
                        ? json_decode($invoice->line_items, true)
                        : $invoice->line_items;
                @endphp

                @if($lineItems && is_array($lineItems))
                    @foreach($lineItems as $item)
                    <tr>
                        <td>{{ $item['description'] ?? 'Υπηρεσίες' }}</td>
                        <td class="text-center">{{ $item['quantity'] ?? 1 }}</td>
                        <td class="text-right">{{ number_format($item['unit_price'] ?? $invoice->subtotal, 2) }} €</td>
                        <td class="text-right">{{ $item['vat_rate'] ?? 24 }}%</td>
                        <td class="text-right">{{ number_format($item['total'] ?? $invoice->total, 2) }} €</td>
                    </tr>
                    @endforeach
                @else
                    {{-- Default single line item --}}
                    <tr>
                        <td>
                            @if($project)
                                Υπηρεσίες για έργο: {{ $project->title }}
                            @else
                                Υπηρεσίες παροχής λογισμικού
                            @endif
                        </td>
                        <td class="text-center">1</td>
                        <td class="text-right">{{ number_format($invoice->subtotal, 2) }} €</td>
                        <td class="text-right">24%</td>
                        <td class="text-right">{{ number_format($invoice->total, 2) }} €</td>
                    </tr>
                @endif
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals">
            <div class="totals-row">
                <div class="totals-label">Καθαρή Αξία:</div>
                <div class="totals-value">{{ number_format($invoice->subtotal, 2) }} €</div>
            </div>
            <div class="totals-row">
                <div class="totals-label">ΦΠΑ (24%):</div>
                <div class="totals-value">{{ number_format($invoice->vat_amount, 2) }} €</div>
            </div>
            <div class="totals-row final">
                <div class="totals-label">ΣΥΝΟΛΟ:</div>
                <div class="totals-value">{{ number_format($invoice->total, 2) }} €</div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p><strong>Τρόπος Πληρωμής:</strong> {{ ucfirst($payment->gateway ?? 'Online') }} ({{ $payment->gateway_payment_id ?? 'N/A' }})</p>
            <p><strong>Κατάσταση:</strong> {{ $payment->status === 'paid' ? 'Εξοφλημένο' : 'Εκκρεμεί' }}</p>

            <div class="greek-legal">
                Το παρόν τιμολόγιο εκδόθηκε σύμφωνα με την ελληνική φορολογική νομοθεσία και διαβιβάστηκε στην ΑΑΔΕ μέσω myDATA.
                Για επαλήθευση της γνησιότητας, σαρώστε τον παραπάνω κωδικό QR ή επισκεφθείτε το myDATA portal με τον ΜΑΡΚ.
            </div>
        </div>
    </div>
</body>
</html>
