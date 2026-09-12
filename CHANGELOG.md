# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.0.0] - 2026-09-12

### Added

- Added the current public Mistral AI API surface, including v2 Prompts and Skills; voice and speech; observability; connectors; workflows; RAG; identity; and beta administration APIs.
- Added a runnable, resource-grouped PHP example for every registered SDK method, plus dedicated streaming examples and shared file, audio, and PDF fixtures.
- Added the public `MistralAI::request()` method for integrations that select endpoint names dynamically.
- Added endpoint metadata for request body types, file fields, API base paths, streaming routes, and administrative operations.
- Added structured `MistralAIException` response metadata, including the parsed API error, raw body, response headers, status code, and request ID.
- Added `StreamingClientInterface` for transports that can expose an SSE response before it has been fully buffered.

### Changed

- Kept the minimum requirement at PHP 8.1. CI now tests PHP 8.1, 8.2, 8.3, 8.4, and 8.5.
- Reworked the client around a compact PSR-17/PSR-18 transport architecture with consistent calling conventions.
- Body-only calls now use a single body array. Calls containing path parameters may use either one combined array or the legacy path/body two-array form.
- GET and DELETE parameters are encoded as RFC 3986 query parameters, and path segments are URL encoded.
- Multipart uploads are copied as raw bytes into a temporary stream instead of being assembled in a single in-memory string.
- SSE callbacks now receive decoded event arrays and handle split chunks, multiline data, mixed line endings, comments, and `[DONE]` frames.
- Streaming calls return their PSR-7 response after invoking the callback instead of returning `null`.
- Updated Composer constraints, workflows, README, endpoint catalog, and `.env.example` to match the current SDK surface.
- Composer distribution archives now export only `src/` and `composer.json`.

### Removed

- Removed endpoints that are no longer present in the current public API reference, including the former fine-tuning job helpers.
- Removed the unreleased DTO and endpoint-trait experiment from the 4.0 development branch; the stable array-based API remains the supported compatibility surface.

## [3.0.1] - 2025-11-20

### Fixed

- Audio transcription helpers (sync + streaming) now submit multipart/form-data payloads even when using remote media via `file_url`, matching the requirements from the official API docs.

### Changed

- Removed the undocumented `audio_url`/`audio_id` aliases; clients must send `file`, `file_url`, or `file_id`, aligning the SDK surface with the server contract.

## [3.0.0] - 2025-11-18

### Added

- Coverage for every endpoint currently documented at [docs.mistral.ai/api](https://docs.mistral.ai/api), including:
    - Batch jobs, agents (beta), conversations (beta), knowledge libraries (beta), and the latest moderation/classification APIs.
    - Audio transcription helpers (sync + streaming), OCR, file downloads, signed URLs, and knowledge-document utilities.
    - Dedicated streaming helpers (`createAudioTranscriptionStream`, `startConversationStream`, etc.) that automatically enable server-sent events.
- Builder metadata describing streaming endpoints so the client can set the correct transport and headers automatically.
- Example scripts for each endpoint, organized under `examples/` by resource category.

### Changed

- `MistralAI::__call` now detects whether the target endpoint contains path placeholders, allowing you to pass request payloads as the first argument when no placeholders are needed (the previous two-array pattern still works).
- GET/DELETE requests now serialize `$options` as query parameters instead of sending empty JSON bodies, and streaming calls advertise `Accept: text/event-stream`.
- Added support for explicit query strings on POST/PATCH endpoints through the reserved `query` option (automatically mapped to `_query` internally).
- Documentation and tests were refreshed to describe the expanded surface area, new helper methods, and the revised calling conventions.

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
