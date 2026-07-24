<?php
/**
 * Payment Gateway Integration
 * Support for VNPay, MoMo, and Cash payment methods
 */

class PaymentGateway {
    
    /**
     * Generate VNPay Payment Link
     */
    public static function generateVNPayLink($booking_id, $order_id, $amount) {
        // VNPay Configuration
        $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_Returnurl = "http://localhost/badminton_booking/payment-callback.php";
        $vnp_TmnCode = "YOUR_VNPAY_TMN_CODE";
        $vnp_HashSecret = "YOUR_VNPAY_HASH_SECRET";
        
        $vnp_Amount = $amount * 100; // VNPay requires amount in cents
        $vnp_Locale = "vn";
        $vnp_BankCode = "";
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
        
        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => "Thanh toan san caul ong booking ID: " . $order_id,
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $order_id
        );
        
        if (isset($vnp_BankCode) && $vnp_BankCode != "") {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }
        
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) $hashdata .= "&" . urlencode($key) . "=" . urlencode($value);
            else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }
        
        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }
        
        return $vnp_Url;
    }
    
    /**
     * Generate MoMo Payment Link
     */
    public static function generateMoMoLink($booking_id, $amount) {
        // MoMo Configuration
        $endpoint = "https://test-payment.momo.vn/v1/direct";
        $partnerCode = "MOMO";
        $accessKey = "F8590EC41DC91FF5";
        $secretkey = "cc5e90977b6c9b6b15c0765108f7652d";
        
        $orderId = "BOOKING_" . $booking_id . "_" . time();
        $orderInfo = "Thanh toan san caul ong";
        $returnUrl = "http://localhost/badminton_booking/payment-callback.php";
        $notifyUrl = "http://localhost/badminton_booking/payment-webhook.php";
        
        $requestId = time() . "";
        $requestType = "captureMoMoWallet";
        $extraData = "";
        
        $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . 
                   "&ipnUrl=" . $notifyUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . 
                   "&partnerCode=" . $partnerCode . "&redirectUrl=" . $returnUrl . 
                   "&requestId=" . $requestId . "&requestType=" . $requestType;
        
        $signature = hash_hmac("sha256", $rawHash, $secretkey);
        
        $data = array(
            'partnerCode' => $partnerCode,
            'partnerName' => "BadmintonPro",
            "accessKey" => $accessKey,
            "requestId" => $requestId,
            "amount" => $amount,
            "orderId" => $orderId,
            "orderInfo" => $orderInfo,
            "returnUrl" => $returnUrl,
            "notifyUrl" => $notifyUrl,
            "extraData" => $extraData,
            "requestType" => $requestType,
            "signature" => $signature,
            "lang" => "vi"
        );
        
        return array(
            'endpoint' => $endpoint,
            'data' => $data
        );
    }
    
    /**
     * Handle Cash Payment
     */
    public static function processCashPayment($booking_id) {
        global $mysqli;
        
        $stmt = $mysqli->prepare('UPDATE bookings SET payment_status = ? WHERE id = ?');
        $payment_status = 'unpaid';
        $stmt->bind_param('si', $payment_status, $booking_id);
        $stmt->execute();
        $stmt->close();
        
        return true;
    }
    
    /**
     * Verify & Process Payment Response
     */
    public static function verifyPayment($payment_method, $response_data) {
        if ($payment_method === 'vnpay') {
            return self::verifyVNPayResponse($response_data);
        } elseif ($payment_method === 'momo') {
            return self::verifyMoMoResponse($response_data);
        }
        
        return false;
    }
    
    private static function verifyVNPayResponse($data) {
        // TODO: Implement VNPay response verification
        return isset($data['vnp_ResponseCode']) && $data['vnp_ResponseCode'] == '00';
    }
    
    private static function verifyMoMoResponse($data) {
        // TODO: Implement MoMo response verification
        return isset($data['resultCode']) && $data['resultCode'] == 0;
    }
}
?>
