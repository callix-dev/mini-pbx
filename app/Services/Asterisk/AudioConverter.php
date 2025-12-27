<?php

namespace App\Services\Asterisk;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class AudioConverter
{
    /**
     * Convert audio file to Asterisk-compatible format (WAV: 8kHz, mono, 16-bit PCM)
     */
    public function convert(string $inputPath, ?string $outputPath = null): ?string
    {
        if (!file_exists($inputPath)) {
            Log::error("Audio file not found: {$inputPath}");
            return null;
        }

        // Generate output path if not provided
        if (!$outputPath) {
            $pathInfo = pathinfo($inputPath);
            $outputPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_converted.wav';
        }

        // FFmpeg command for Asterisk-compatible audio
        $command = sprintf(
            'ffmpeg -y -i %s -acodec pcm_s16le -ar 8000 -ac 1 %s 2>&1',
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        try {
            $result = Process::run($command);

            if ($result->successful() && file_exists($outputPath)) {
                Log::info("Audio converted successfully: {$outputPath}");
                return $outputPath;
            }

            Log::error("Audio conversion failed: " . $result->output());
            return null;
        } catch (\Exception $e) {
            Log::error("Audio conversion error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get audio file duration in seconds
     */
    public function getDuration(string $filePath): ?int
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $command = sprintf(
            'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
            escapeshellarg($filePath)
        );

        try {
            $result = Process::run($command);

            if ($result->successful()) {
                return (int) floor((float) trim($result->output()));
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Failed to get audio duration: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Convert to multiple formats for Asterisk
     */
    public function convertToAsteriskFormats(string $inputPath, string $outputDir): array
    {
        $pathInfo = pathinfo($inputPath);
        $baseName = $pathInfo['filename'];
        $results = [];

        // WAV (G.711 μ-law)
        $wavPath = "{$outputDir}/{$baseName}.wav";
        $command = sprintf(
            'ffmpeg -y -i %s -acodec pcm_mulaw -ar 8000 -ac 1 %s 2>&1',
            escapeshellarg($inputPath),
            escapeshellarg($wavPath)
        );
        
        $result = Process::run($command);
        $results['wav'] = $result->successful() && file_exists($wavPath) ? $wavPath : null;

        // GSM
        $gsmPath = "{$outputDir}/{$baseName}.gsm";
        $command = sprintf(
            'ffmpeg -y -i %s -acodec libgsm -ar 8000 -ac 1 %s 2>&1',
            escapeshellarg($inputPath),
            escapeshellarg($gsmPath)
        );
        
        $result = Process::run($command);
        $results['gsm'] = $result->successful() && file_exists($gsmPath) ? $gsmPath : null;

        // SLN (signed linear)
        $slnPath = "{$outputDir}/{$baseName}.sln";
        $command = sprintf(
            'ffmpeg -y -i %s -f s16le -acodec pcm_s16le -ar 8000 -ac 1 %s 2>&1',
            escapeshellarg($inputPath),
            escapeshellarg($slnPath)
        );
        
        $result = Process::run($command);
        $results['sln'] = $result->successful() && file_exists($slnPath) ? $slnPath : null;

        return $results;
    }

    /**
     * Check if FFmpeg is available
     */
    public function isAvailable(): bool
    {
        try {
            $result = Process::run('which ffmpeg');
            return $result->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}



