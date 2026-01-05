<?php
class ImageHandler {
    /**
     * Redimensiona e salva uma imagem
     * @param array $file O arquivo $_FILES['imagem']
     * @param string $targetDir O diretório de destino (ex: ../img/projetos/)
     * @param string $targetName O nome final do arquivo (ex: projeto_123.webp)
     * @param int $maxWidth Largura máxima permitida (ex: 1024px)
     * @return bool True se sucesso, False se falha
     */
    public static function resizeAndSave($file, $targetDir, $targetName, $maxWidth = 1024) {
        $sourcePath = $file['tmp_name'];
        $destPath = $targetDir . $targetName;
        
        // 1. Obter informações da imagem original
        list($width, $height, $type) = getimagesize($sourcePath);
        
        // Se não for imagem válida, retorna erro
        if (!$width) return false;

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
                return false; // Tipo não suportado
        }

        // 5. Copiar e redimensionar a imagem original para a nova
        imagecopyresampled(
            $newImage, $source, 
            0, 0, 0, 0, 
            $newWidth, $newHeight, 
            $width, $height
        );

        // 6. Salvar no disco (Aqui convertemos tudo para WEBP ou JPG para padronizar e comprimir)
        // Vamos salvar mantendo a extensão original ou forçar WEBP/JPG se preferires.
        // Para este exemplo, salvamos baseado na extensão do nome de destino.
        
        $ext = strtolower(pathinfo($targetName, PATHINFO_EXTENSION));
        $success = false;

        if ($ext == 'jpg' || $ext == 'jpeg') {
            $success = imagejpeg($newImage, $destPath, 85); // Qualidade 85% (Compressão)
        } elseif ($ext == 'png') {
            $success = imagepng($newImage, $destPath, 8); // Compressão 0-9
        } elseif ($ext == 'webp') {
            $success = imagewebp($newImage, $destPath, 80); // Qualidade 80%
        }

        // 7. Limpar memória
        imagedestroy($newImage);
        imagedestroy($source);

        return $success;
    }
}
?>