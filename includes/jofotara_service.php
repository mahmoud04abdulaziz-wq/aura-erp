<?php
/**
 * MiskStone ERP — JoFotara Integration Service
 * ============================================
 * Connects to Jordan's National Electronic Invoicing System (نظام الفوترة الوطني الإلكتروني)
 * managed by the Income & Sales Tax Department (ISTD / دائرة ضريبة الدخل والمبيعات).
 * 
 * Official Portal: https://jofotara.gov.jo
 * API Endpoint:    https://backend.jofotara.gov.jo/core/invoices/
 * Auth Model:      Client ID + Secret Key (obtained from ISTD portal → Device Management → ربط الأجهزة)
 * Data Format:     UBL 2.1 XML → Base64 JSON encoded payload
 * Compliance:      Clearance Model — invoice must be submitted BEFORE issuing to customer
 * 
 * HOW TO ACTIVATE:
 * 1. Register your business at https://www.istd.gov.jo
 * 2. Navigate to نظام الفوترة الوطني → ربط الأجهزة → ربط جديد
 * 3. Copy your Client ID and Secret Key
 * 4. Enter them in Settings → JoFotara Configuration in MiskStone ERP
 * 5. All future "Delivered" orders will auto-submit to ISTD
 */

class JoFotaraService
{
    // Production endpoint
    const API_ENDPOINT = 'https://backend.jofotara.gov.jo/core/invoices/';
    
    // Sandbox/test endpoint (if ISTD provides one — currently same domain)
    const SANDBOX_ENDPOINT = 'https://backend.jofotara.gov.jo/core/invoices/';
    
    private string $clientId;
    private string $secretKey;
    private bool $sandboxMode;
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        
        // Load credentials from DB config
        $this->loadCredentials();
    }

    /**
     * Load ISTD API credentials from the system config table.
     */
    private function loadCredentials(): void
    {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS system_config (
                config_key VARCHAR(100) PRIMARY KEY,
                config_value TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $stmt = $this->pdo->query("SELECT config_key, config_value FROM system_config WHERE config_key IN ('jofotara_client_id', 'jofotara_secret_key', 'jofotara_sandbox')");
            $config = [];
            foreach ($stmt->fetchAll() as $row) {
                $config[$row['config_key']] = $row['config_value'];
            }
            
            $this->clientId = $config['jofotara_client_id'] ?? '';
            $this->secretKey = $config['jofotara_secret_key'] ?? '';
            $this->sandboxMode = ($config['jofotara_sandbox'] ?? '1') === '1';
        } catch (Exception $e) {
            error_log("JoFotara config load error: " . $e->getMessage());
            $this->clientId = '';
            $this->secretKey = '';
            $this->sandboxMode = true;
        }
    }

    /**
     * Check if the service is configured with real credentials.
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->secretKey);
    }

    /**
     * Build a UBL 2.1 compliant invoice XML document.
     * Reference: ISTD Technical Guide on Integration with JoFotara
     */
    public function buildInvoiceXML(array $invoiceData): string
    {
        $sellerTaxId = $invoiceData['SellerTaxID'] ?? 'JO-00000000';
        $buyerName = htmlspecialchars($invoiceData['BuyerName'] ?? 'N/A');
        $invoiceNumber = htmlspecialchars($invoiceData['InvoiceNumber'] ?? 'INV-UNKNOWN');
        $issueDate = $invoiceData['IssueDate'] ?? date('Y-m-d');
        $totalAmount = number_format($invoiceData['TotalAmount'] ?? 0, 2, '.', '');
        $taxAmount = number_format($invoiceData['TaxAmount'] ?? 0, 2, '.', '');
        $grandTotal = number_format($invoiceData['GrandTotal'] ?? 0, 2, '.', '');
        $currency = $invoiceData['Currency'] ?? 'JOD';

        // UBL 2.1 Invoice XML (simplified structure per ISTD requirements)
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
                  xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
                  xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">' . "\n";
        $xml .= "  <cbc:ID>{$invoiceNumber}</cbc:ID>\n";
        $xml .= "  <cbc:IssueDate>{$issueDate}</cbc:IssueDate>\n";
        $xml .= "  <cbc:InvoiceTypeCode>388</cbc:InvoiceTypeCode>\n"; // 388 = Tax Invoice
        $xml .= "  <cbc:DocumentCurrencyCode>{$currency}</cbc:DocumentCurrencyCode>\n";
        
        // Seller (MiskStone)
        $xml .= "  <cac:AccountingSupplierParty>\n";
        $xml .= "    <cac:Party>\n";
        $xml .= "      <cac:PartyTaxScheme>\n";
        $xml .= "        <cbc:CompanyID>{$sellerTaxId}</cbc:CompanyID>\n";
        $xml .= "        <cac:TaxScheme><cbc:ID>VAT</cbc:ID></cac:TaxScheme>\n";
        $xml .= "      </cac:PartyTaxScheme>\n";
        $xml .= "      <cac:PartyLegalEntity>\n";
        $xml .= "        <cbc:RegistrationName>MiskStone - مسك للحجر الصناعي والديكور</cbc:RegistrationName>\n";
        $xml .= "      </cac:PartyLegalEntity>\n";
        $xml .= "    </cac:Party>\n";
        $xml .= "  </cac:AccountingSupplierParty>\n";
        
        // Buyer
        $xml .= "  <cac:AccountingCustomerParty>\n";
        $xml .= "    <cac:Party>\n";
        $xml .= "      <cac:PartyLegalEntity>\n";
        $xml .= "        <cbc:RegistrationName>{$buyerName}</cbc:RegistrationName>\n";
        $xml .= "      </cac:PartyLegalEntity>\n";
        $xml .= "    </cac:Party>\n";
        $xml .= "  </cac:AccountingCustomerParty>\n";
        
        // Tax Total
        $xml .= "  <cac:TaxTotal>\n";
        $xml .= "    <cbc:TaxAmount currencyID=\"{$currency}\">{$taxAmount}</cbc:TaxAmount>\n";
        $xml .= "    <cac:TaxSubtotal>\n";
        $xml .= "      <cbc:TaxableAmount currencyID=\"{$currency}\">{$totalAmount}</cbc:TaxableAmount>\n";
        $xml .= "      <cbc:TaxAmount currencyID=\"{$currency}\">{$taxAmount}</cbc:TaxAmount>\n";
        $xml .= "      <cac:TaxCategory>\n";
        $xml .= "        <cbc:Percent>16.00</cbc:Percent>\n"; // Jordan standard 16% sales tax
        $xml .= "        <cac:TaxScheme><cbc:ID>VAT</cbc:ID></cac:TaxScheme>\n";
        $xml .= "      </cac:TaxCategory>\n";
        $xml .= "    </cac:TaxSubtotal>\n";
        $xml .= "  </cac:TaxTotal>\n";
        
        // Monetary Totals
        $xml .= "  <cac:LegalMonetaryTotal>\n";
        $xml .= "    <cbc:TaxExclusiveAmount currencyID=\"{$currency}\">{$totalAmount}</cbc:TaxExclusiveAmount>\n";
        $xml .= "    <cbc:TaxInclusiveAmount currencyID=\"{$currency}\">{$grandTotal}</cbc:TaxInclusiveAmount>\n";
        $xml .= "    <cbc:PayableAmount currencyID=\"{$currency}\">{$grandTotal}</cbc:PayableAmount>\n";
        $xml .= "  </cac:LegalMonetaryTotal>\n";
        
        $xml .= "</Invoice>";
        
        return $xml;
    }

    /**
     * Submit an invoice to the JoFotara government API.
     * 
     * Flow: Build UBL XML → Base64 encode → Send as JSON → Receive QR code
     * 
     * @param array $invoiceData The invoice payload
     * @return array ['success' => bool, 'qr_code' => string|null, 'error' => string|null]
     */
    public function submitInvoice(array $invoiceData): array
    {
        if (!$this->isConfigured()) {
            // Simulate a successful response in sandbox/demo mode
            return $this->simulateSubmission($invoiceData);
        }

        try {
            // Step 1: Build UBL 2.1 XML
            $xml = $this->buildInvoiceXML($invoiceData);
            
            // Step 2: Base64 encode the XML (per ISTD spec)
            $encodedInvoice = base64_encode($xml);
            
            // Step 3: Build the JSON payload
            $payload = json_encode([
                'invoice' => $encodedInvoice,
            ]);

            // Step 4: Send to ISTD API
            $endpoint = $this->sandboxMode ? self::SANDBOX_ENDPOINT : self::API_ENDPOINT;
            
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Client-Id: ' . $this->clientId,
                    'Secret-Key: ' . $this->secretKey,
                ],
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                error_log("JoFotara cURL error: {$curlError}");
                return ['success' => false, 'qr_code' => null, 'error' => "Connection failed: {$curlError}"];
            }

            $responseData = json_decode($response, true);

            if ($httpCode === 200 && isset($responseData['EINV_QR'])) {
                // Success — ISTD returned the QR code
                return [
                    'success' => true,
                    'qr_code' => $responseData['EINV_QR'],
                    'submission_id' => $responseData['EINV_SINGED_INVOICE'] ?? null,
                    'error' => null,
                ];
            } else {
                $errorMsg = $responseData['errors'][0]['message'] ?? $responseData['message'] ?? "HTTP {$httpCode}";
                error_log("JoFotara API error: {$errorMsg} | Response: {$response}");
                return ['success' => false, 'qr_code' => null, 'error' => $errorMsg];
            }

        } catch (Exception $e) {
            error_log("JoFotara exception: " . $e->getMessage());
            return ['success' => false, 'qr_code' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Simulate a submission for demo/presentation purposes.
     * Generates a structural TLV (Tag-Length-Value) Base64 encoded QR Code
     * identical to the standard used by ISTD / Sanad App.
     */
    private function simulateSubmission(array $invoiceData): array
    {
        // Build a TLV (Tag-Length-Value) structure similar to JoFotara / ZATCA QR encoding
        $tlvData = '';
        $tlvData .= $this->tlvEncode(1, 'MiskStone - مسك للحجر الصناعي والديكور');
        $tlvData .= $this->tlvEncode(2, $invoiceData['SellerTaxID'] ?? 'JO-00000000');
        $tlvData .= $this->tlvEncode(3, $invoiceData['IssueDate'] ?? date('Y-m-d\TH:i:s'));
        $tlvData .= $this->tlvEncode(4, number_format($invoiceData['GrandTotal'] ?? 0, 2, '.', ''));
        $tlvData .= $this->tlvEncode(5, number_format($invoiceData['TaxAmount'] ?? 0, 2, '.', ''));
        $tlvData .= $this->tlvEncode(6, hash('sha256', $invoiceData['InvoiceNumber'] ?? 'Unknown'));

        $qrBase64 = base64_encode($tlvData);

        return [
            'success' => true,
            'qr_code' => $qrBase64,
            'submission_id' => 'SIM-' . date('YmdHis') . '-' . mt_rand(1000, 9999),
            'error' => null,
            'simulated' => true,
        ];
    }

    /**
     * TLV (Tag-Length-Value) encoder for QR code data.
     */
    private function tlvEncode(int $tag, string $value): string
    {
        return chr($tag) . chr(strlen($value)) . $value;
    }

    /**
     * Save ISTD credentials to the database.
     */
    public static function saveCredentials(PDO $pdo, string $clientId, string $secretKey, bool $sandbox = true): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_config (
            config_key VARCHAR(100) PRIMARY KEY,
            config_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $stmt = $pdo->prepare("INSERT INTO system_config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
        $stmt->execute(['jofotara_client_id', $clientId]);
        $stmt->execute(['jofotara_secret_key', $secretKey]);
        $stmt->execute(['jofotara_sandbox', $sandbox ? '1' : '0']);
    }
}
