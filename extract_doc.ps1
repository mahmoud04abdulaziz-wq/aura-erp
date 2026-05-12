Add-Type -AssemblyName System.IO.Compression.FileSystem
$docPath = "c:\xampp\htdocs\grad-project-prototype\sysanalysis_doc.docx"
$outPath = "c:\xampp\htdocs\grad-project-prototype\sysanalysis_text.txt"

$zipArchive = [System.IO.Compression.ZipFile]::OpenRead($docPath)
$docEntry = $zipArchive.GetEntry("word/document.xml")
$streamReader = New-Object System.IO.StreamReader($docEntry.Open())
$xmlContent = $streamReader.ReadToEnd()
$streamReader.Close()
$zipArchive.Dispose()

$plainText = $xmlContent -replace '<[^>]+>',' ' -replace '\s+',' '
[System.IO.File]::WriteAllText($outPath, $plainText)
Write-Host "Extracted $($plainText.Length) characters"
