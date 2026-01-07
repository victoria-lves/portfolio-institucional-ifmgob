<?php
class ImageHandler {
    /**
     * Redimensiona e salva uma imagem com fallback de segurança
     */
    public static function resizeAndSave($file, $targetDir, $targetName, $maxWidth = 1024) {
        $sourcePath = $file['tmp_name'];
        $destPath = $targetDir . $targetName;

        // VERIFICAÇÃO DE SEGURANÇA: Se a biblioteca GD não estiver ativa
        if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
            // Apenas move o arquivo original sem redimensionar (evita o erro fatal)
            return move_uploaded_file($sourcePath, $destPath);
        }
        
        // 1. Obter informações da imagem original
        // O @ suprime erros se o arquivo não for uma imagem válida
        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) return false;

        list($width, $height, $type) = $imageInfo;

        // 2. Calcular novas dimensões (Mantendo Proporção)
        $ratio = $width / $height;
        
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = $maxWidth / $ratio;
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }

        // 3. Criar uma nova imagem em branco na memória
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // 4. Carregar a imagem original baseada no tipo
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($sourcePath);
                // Manter transparência do PNG
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($sourcePath);
                // Manter transparência do WEBP
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                break;
            default:
                // Tipo não suportado pelo GD, tenta mover o arquivo original
                return move_uploaded_file($sourcePath, $destPath);
        }

        if (!$source) {
            return move_uploaded_file($sourcePath, $destPath);
        }

        // 5. Copiar e redimensionar a imagem original para a nova
        imagecopyresampled(
            $newImage, $source, 
            0, 0, 0, 0, 
            $newWidth, $newHeight, 
            $width, $height
        );

        // 6. Salvar no disco
        $ext = strtolower(pathinfo($targetName, PATHINFO_EXTENSION));
        $success = false;

        if ($ext == 'jpg' || $ext == 'jpeg') {
            $success = imagejpeg($newImage, $destPath, 85);
        } elseif ($ext == 'png') {
            $success = imagepng($newImage, $destPath, 8);
        } elseif ($ext == 'webp') {
            $success = imagewebp($newImage, $destPath, 80);
        } else {
            // Extensão desconhecida para as funções de salvamento, tenta JPG
            $success = imagejpeg($newImage, $destPath, 85);
        }

        // 7. Limpar memória
        imagedestroy($newImage);
        imagedestroy($source);

        return $success;
    }
}
?>