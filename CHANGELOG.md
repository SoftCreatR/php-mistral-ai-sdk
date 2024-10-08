# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2024-10-06

### Removed

- Dropped support for PHP 7.4. **PHP 8.1 or higher is now required**.

### Added

- **Streaming Support**:
    - Added support for streaming responses in the `MistralAI` class, allowing real-time token generation for the `createChatCompletion` method and other applicable endpoints.
    - Implemented a callback mechanism for handling streamed data in real time.

- **New Endpoints**:
    - **Models**:
        - `retrieveModel`: Retrieve information about a specific model by its ID.
        - `deleteModel`: Delete a fine-tuned model by its ID.
        - `updateFineTunedModel`: Update fine-tuning for a specific model.
        - `archiveModel`: Archive a fine-tuned model.
        - `unarchiveModel`: Unarchive a fine-tuned model.
    - **Files**:
        - `uploadFile`: Upload a file for use in fine-tuning.
        - `listFiles`: Retrieve a list of all uploaded files.
        - `retrieveFile`: Retrieve details of a specific file by its ID.
        - `deleteFile`: Delete a file by its ID.
    - **Fine-Tuning Jobs**:
        - `listFineTuningJobs`: Get a list of all fine-tuning jobs.
        - `retrieveFineTuningJob`: Retrieve details of a specific fine-tuning job by its ID.
        - `cancelFineTuningJob`: Cancel a fine-tuning job.
        - `startFineTuningJob`: Start a fine-tuning job.
        - `createFineTuningJob`: Create a new fine-tuning job.
    - **FIM Completion**:
        - `createFimCompletion`: Generate Fill-In-the-Middle (FIM) text completions.
    - **Agents Completion**:
        - `createAgentsCompletion`: Generate completions based on agents or specialized workflows.

- **New Examples**:
    - Created new example files to showcase API usage and functionality. All examples were aligned with the OpenAPI specification, using relevant model descriptions and proper documentation:
        - **Chat Completion with Streaming**: `examples/chat/createChatCompletion.php`
            - Demonstrates how to create a chat completion with the `mistral-small-latest` model, featuring real-time response streaming.
        - **Retrieve Model**: `examples/models/retrieveModel.php`
            - Demonstrates how to retrieve detailed information about a specific model using the `retrieveModel` endpoint.
        - **Delete Model**: `examples/models/deleteModel.php`
            - Shows how to delete a specific model using the `deleteModel` endpoint.
        - **Archive/Unarchive Model**:
            - `examples/models/archiveModel.php`
            - `examples/models/unarchiveModel.php`
            - These examples show how to archive and unarchive models respectively.
        - **File Management**:
            - **Upload File**: `examples/files/uploadFile.php`
            - **List Files**: `examples/files/listFiles.php`
            - **Retrieve File**: `examples/files/retrieveFile.php`
            - **Delete File**: `examples/files/deleteFile.php`
        - **Fine-Tuning Jobs**:
            - **List Fine-Tuning Jobs**: `examples/finetuning/listFineTuningJobs.php`
            - **Retrieve Fine-Tuning Job**: `examples/finetuning/retrieveFineTuningJob.php`
            - **Cancel Fine-Tuning Job**: `examples/finetuning/cancelFineTuningJob.php`
            - **Start Fine-Tuning Job**: `examples/finetuning/startFineTuningJob.php`
            - **Create Fine-Tuning Job**: `examples/finetuning/createFineTuningJob.php`
        - **FIM Completion**: `examples/fim/createFimCompletion.php`
            - Demonstrates the `createFimCompletion` method to generate Fill-In-the-Middle completions.
        - **Agents Completion**: `examples/agents/createAgentsCompletion.php`
            - Shows how to use the `createAgentsCompletion` method to generate agents-based text completions.

- **Factory Updates**:
    - Added real-time processing of streamed content in the `MistralAIFactory::request` method.

- **Enhanced Documentation**:
    - Provided detailed docblocks for all example files.
    - Example docblocks include model descriptions, usage instructions, expected output, and references to the OpenAPI specification.

### Changed

- **Refactor of the `MistralAI` class**:
    - Adjusted the `__call` method to better align with the updated `MistralAIURLBuilder`, ensuring proper extraction of parameters and options.
    - Enhanced error handling and validation for parameter inputs to improve robustness.
    - Updated request generation to handle both JSON and multipart request bodies, as required by specific API endpoints (e.g., file uploads).
    - Implemented better multipart handling for file upload scenarios.
- **Refactor of the Unit Tests**:
    - Updated unit tests to reflect the new API endpoints and streaming capabilities.
    - Added new test cases for the new endpoints and streaming functionality.


## [1.0.0] - 2024-02-03

### Added

- Initial release of the Mistral AI PHP library.
- Basic implementation for making API calls to the Mistral API.
- Unit tests for the initial implementation.
