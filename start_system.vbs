Set shell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
' Get the directory where this script is located
scriptDir = fso.GetParentFolderName(WScript.ScriptFullName)
' Launch the HTA splash screen using absolute path
shell.Run "mshta.exe """ & scriptDir & "\splash.hta""", 1, False


