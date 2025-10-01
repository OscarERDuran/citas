<?php
/**
 * Manejador JWT (JSON Web Tokens)
 */

class JWTHandler {
    
    /**
     * Generar token JWT
     */
    public function generateToken($payload, $expiration = null) {
        if (!$expiration) {
            $expiration = time() + JWT_EXPIRATION;
        }
        
        // Header
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => JWT_ALGORITHM
        ]);
        
        // Payload
        $payload['iat'] = time(); // Issued at
        $payload['exp'] = $expiration; // Expiration
        $payloadJson = json_encode($payload);
        
        // Encode
        $headerEncoded = $this->base64UrlEncode($header);
        $payloadEncoded = $this->base64UrlEncode($payloadJson);
        
        // Signature
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, JWT_SECRET, true);
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }
    
    /**
     * Verificar token JWT
     */
    public function verifyToken($token) {
        try {
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                return false;
            }
            
            [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;
            
            // Verificar signature
            $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, JWT_SECRET, true);
            $signatureValid = $this->base64UrlEncode($signature);
            
            if ($signatureEncoded !== $signatureValid) {
                return false;
            }
            
            // Decodificar payload
            $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
            
            // Verificar expiración
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }
            
            return $payload;
            
        } catch (Exception $e) {
            error_log('Error verificando JWT: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Decodificar payload sin verificar (para debugging)
     */
    public function decodePayload($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        return json_decode($this->base64UrlDecode($parts[1]), true);
    }
    
    /**
     * Base64 URL encode
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
?>
