# Set error action to stop on script errors
$ErrorActionPreference = "Stop"

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "  Building SiswaMart Core Zip Package     " -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

# Determine script root directory safely
$rootDir = if ($PSScriptRoot) { $PSScriptRoot } else { (Get-Location).Path }

# Step 1: Run npm run build
Write-Host "`n[1/3] Running 'npm run build'..." -ForegroundColor Yellow
try {
    npm run build
    if ($LASTEXITCODE -ne 0) {
        throw "npm run build exited with code $LASTEXITCODE"
    }
    Write-Host "[OK] Asset compilation completed successfully." -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Failed to run 'npm run build': $_" -ForegroundColor Red
    exit 1
}

# Define target zip name & items to include
$zipFileName = "siswamart_core.zip"
$zipFilePath = Join-Path -Path $rootDir -ChildPath $zipFileName

$foldersToInclude = @('app', 'bootstrap', 'config', 'database', 'resources', 'routes')
$filesToInclude   = @('artisan', 'composer.json', 'composer.lock', 'package.json', 'vite.config.js')

# Remove existing zip if present
if (Test-Path $zipFilePath) {
    Write-Host "`nRemoving existing $zipFileName..." -ForegroundColor Gray
    Remove-Item $zipFilePath -Force
}

# Load .NET Assembly for Compression
Add-Type -AssemblyName "System.IO.Compression"
Add-Type -AssemblyName "System.IO.Compression.FileSystem"

Write-Host "`n[2/3] Compressing files with Linux-compatible forward slashes (/)..." -ForegroundColor Yellow

# Open Zip Archive
$zipStream = [System.IO.File]::Open($zipFilePath, [System.IO.FileMode]::Create)
$archive   = New-Object System.IO.Compression.ZipArchive($zipStream, [System.IO.Compression.ZipArchiveMode]::Create)

$totalFilesAdded = 0

try {
    # 1. Process explicit individual files
    foreach ($file in $filesToInclude) {
        $fullPath = Join-Path -Path $rootDir -ChildPath $file
        if (Test-Path $fullPath -PathType Leaf) {
            $entryName = $file.Replace('\', '/')
            [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                $archive,
                $fullPath,
                $entryName,
                [System.IO.Compression.CompressionLevel]::Optimal
            ) | Out-Null
            $totalFilesAdded++
            Write-Host "  + Added file: $entryName" -ForegroundColor DarkGray
        } else {
            Write-Host "  [!] Warning: File not found: $file" -ForegroundColor Yellow
        }
    }

    # 2. Process folders recursively
    foreach ($folder in $foldersToInclude) {
        $folderPath = Join-Path -Path $rootDir -ChildPath $folder
        if (Test-Path $folderPath -PathType Container) {
            $files = Get-ChildItem -Path $folderPath -Recurse -File
            $folderFileCount = 0
            foreach ($item in $files) {
                # Calculate relative path from project root
                $relPath = $item.FullName.Substring($rootDir.Length).TrimStart('\', '/')
                # Ensure Linux forward slash separator
                $entryName = $relPath.Replace('\', '/')
                
                [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                    $archive,
                    $item.FullName,
                    $entryName,
                    [System.IO.Compression.CompressionLevel]::Optimal
                ) | Out-Null
                $folderFileCount++
                $totalFilesAdded++
            }
            Write-Host "  + Added folder: $folder ($folderFileCount items)" -ForegroundColor DarkGray
        } else {
            Write-Host "  [!] Warning: Folder not found: $folder" -ForegroundColor Yellow
        }
    }
} finally {
    $archive.Dispose()
    $zipStream.Dispose()
}

# Step 3: Verification & Success Message
if (Test-Path $zipFilePath) {
    $fileInfo = Get-Item $zipFilePath
    $sizeMB = [math]::Round($fileInfo.Length / 1MB, 2)
    Write-Host "`n[3/3] Package successfully created!" -ForegroundColor Green
    Write-Host "------------------------------------------" -ForegroundColor Cyan
    Write-Host "  File Name   : $zipFileName" -ForegroundColor White
    Write-Host "  Total Files : $totalFilesAdded files" -ForegroundColor White
    Write-Host "  File Size   : $sizeMB MB" -ForegroundColor White
    Write-Host "  Full Path   : $zipFilePath" -ForegroundColor White
    Write-Host "------------------------------------------" -ForegroundColor Cyan
} else {
    Write-Host "[ERROR] Failed to generate $zipFileName" -ForegroundColor Red
    exit 1
}
