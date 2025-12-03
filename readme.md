# Alternative - AI generated metatags for images in TYPO3 with Google Gemini

## Introduction

This TYPO3 extension allows setting alternative texts, title labels and a description for images in a filestorage.
This can be done via file list backend module or via command on the CLI.

Example metadata labels from AI:
![documentation_example1.png](Documentation/Images/documentation_example1.png)

Example backend integration:
![documentation_example2.png](Documentation/Images/documentation_example2.png)

Example CLI command:
![documentation_example3.png](Documentation/Images/documentation_example3.png)

## Google Gemini

- To use the extension, you need a **Google Gemini API** key. You can register for one 
    at https://aistudio.google.com/app/api-keys.

## Installation

```
composer req in2code/alternative
```

After that, you have to set some initial configuration in Extension Manager configuration:

| Title                | Default value | Description                                                                                                                                          |
|----------------------|---------------|------------------------------------------------------------------------------------------------------------------------------------------------------|
| setAlternative       | 1             | Toggle function: Set a value for alternative text                                                                                                    |
| setTitle             | 1             | Toggle function: Set a value for image title                                                                                                         |
| setDescription       | 1             | Toggle function: Set a value for a description                                                                                                       |
| showButtonInFileList | 1             | Show or hide button in backend module file list                                                                                                      |
| apiKey               | -             | Google Gemini API key. You can let this value empty and simply use ENV_VAR "GOOGLE_API_KEY" instead if you want to use CI pipelines for this setting |
| limitToLanguages     | -             | If set, limit to this language identifiers only. Use a commaseparated list of numbers                                                                |

Note: It's recommended to use ENV vars for in2code/alternative instead of saving the API-Key in Extension Manager configuration

```
GOOGLE_API_KEY=your_api_key_from_google
```

## CLI commands

```
# Set metadata for all image files in storage 1
./vendor/bin/typo3 alternative:set "1:/"

# Set metadata for all image files in a subfoler in storage 1 (maybe "fileadmin/in2code/folder/")
./vendor/bin/typo3 alternative:set "1:/in2code/folder/"

# Enforce to set metadata for all image files in storage 1
./vendor/bin/typo3 alternative:set "1:/" 1
```

## Changelog and breaking changes

| Version | Date       | State   | Description                            |
|---------|------------|---------|----------------------------------------|
| 1.0.0   | 2025-12-03 | Task    | Initial release of in2code/alternative |