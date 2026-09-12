<?php

/*
 * Copyright (c) 2024-present, Sascha Greuel and Contributors
 *
 * Permission to use, copy, modify, and/or distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

namespace SoftCreatR\MistralAI;

use Exception;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Random\RandomException;
use SensitiveParameter;
use SoftCreatR\MistralAI\Exception\MistralAIException;
use SoftCreatR\MistralAI\Http\MultipartBodyBuilder;
use SoftCreatR\MistralAI\Http\ServerSentEventDecoder;
use SoftCreatR\MistralAI\Http\StreamingClientInterface;
use Throwable;

use const JSON_THROW_ON_ERROR;
use const PHP_QUERY_RFC3986;

/**
 * PSR-17/PSR-18 client for the Mistral AI API.
 *
 * Registered endpoints can be called as magic methods or through request().
 * See README.md for the complete method catalog.
 *
 * API METHODS START
 * @method ResponseInterface|null activateForConsumerConnector(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null addAdminOrUpdateUsersWorkspaces(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null addAdminUsersWorkspaces(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null aggregateSpans(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null aggregateTraces(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null appendConversation(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null appendConversationStream(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null archiveModel(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null archiveWorkflow(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null assignAdminGroupToWorkspace(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null assignAdminUsersToGroup(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null batchCancelWorkflowExecutions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null batchTerminateWorkflowExecutions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null bulkArchiveWorkflows(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null bulkUnarchiveWorkflows(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null callConnectorTool(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null cancelBatchJob(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null cancelWorkflowExecution(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createAdminApiKey(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createAdminUserGroup(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createAdminUsers(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createAdminWorkspace(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createAgent(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createAgentsCompletion(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createAudioTranscription(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createAudioTranscriptionStream(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createBatchJob(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createCampaign(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createChatClassification(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createChatCompletion(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createChatModeration(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createClassification(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createConnector(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createDataset(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createDatasetRecord(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createDeployment(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createEmbedding(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createFimCompletion(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createJudge(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createLibrary(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createModeration(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createOcr(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createOrUpdateConnectorOrganizationCredentials(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createOrUpdateConnectorUserCredentials(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createOrUpdateConnectorWorkspaceCredentials(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createPrompt(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createPromptVersion(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createSkill(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createSkillVersion(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createSpeech(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createVoice(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deactivateForConsumerConnector(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteAdminApiKey(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteAdminInvite(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteAdminUser(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteAdminUserGroup(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteAdminWorkspaces(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteAgent(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteAgentVersionAlias(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteAllConnectorUserCredentials(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteBatchJob(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteCampaign(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteConnector(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteConnectorOrganizationCredentials(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteConnectorUserCredentials(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteConnectorWorkspaceCredentials(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteConversation(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteDataset(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteDatasetRecord(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteDatasetRecords(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteDeployment(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteFile(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteJudge(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteLibrary(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteLibraryDocument(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteLibraryShare(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteModel(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deletePrompt(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteSkill(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteVoice(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null downloadFile(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null executeWorkflow(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null executeWorkflowRegistration(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null exportDatasetToJsonl(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getCampaignById(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getCampaignSelectedEvents(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getCampaignStatusById(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getCampaigns(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getChatCompletionEvent(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getChatCompletionEventIds(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getChatCompletionEvents(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getChatCompletionFieldOptions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getChatCompletionFieldOptionsCounts(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getChatCompletionFields(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getConfigs(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getConnectorAuthUrl(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getConnectorAuthenticationMethods(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getDatasetById(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getDatasetImportTask(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getDatasetImportTasks(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getDatasetRecord(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getDatasetRecords(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getDatasets(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getDeployment(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getDeploymentLogs(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getDeploymentSummaries(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getIdentity(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getJudgeById(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getJudges(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getLogFieldOptions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getLogFields(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getRun(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getRunHistory(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getSchedule(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getSchedules(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getSimilarChatCompletionEvents(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getSpanById(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getSpanEvaluationFieldOptions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getSpanEvaluationFields(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getSpanFieldOptions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getSpanFields(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getStreamEvents(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getTraceById(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getTraceFieldOptions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getTraceFields(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getTraceSpans(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getVoice(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getVoiceSampleAudio(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getWorkflow(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getWorkflowEvents(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getWorkflowExecution(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getWorkflowExecutionHistory(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getWorkflowExecutionLogs(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getWorkflowExecutionTraceEvents(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getWorkflowExecutionTraceInfo(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getWorkflowExecutionTraceOtel(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getWorkflowExecutionTraceSummary(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getWorkflowMetrics(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getWorkflowRegistration(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getWorkflowRegistrations(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getWorkflows(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null inviteAdminUsers(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null judgeChatCompletionEvent(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null judgeConversation(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null judgeDatasetRecord(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listAdminApiKeys(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listAdminAuditLogs(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listAdminInvite(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listAdminRateLimits(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listAdminRoles(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listAdminSpendLimits(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listAdminUsage(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listAdminUserGroups(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listAdminUsers(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listAdminWorkspaces(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listAgentPages(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listAgentVersionAliases(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listAgentVersions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listAgents(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listBatchJobs(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listConnectors(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listConnectorOrganizationCredentials(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listConnectorTools(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listConnectorUserCredentials(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listConnectorWorkspaceCredentials(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listConversationHistory(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listConversationMessages(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listConversations(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listDeploymentWorkers(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listDeployments(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listFiles(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listLibraries(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listLibraryDocuments(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listLibraryShares(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listModels(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listOrganizations(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listPromptVersions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listPrompts(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listRuns(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listSkillVersions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listSkills(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listVoices(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listWorkspaces(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null patchLibrary(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null patchLibraryDocument(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null pauseSchedule(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null postDatasetRecordsFromCampaign(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null postDatasetRecordsFromDataset(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null postDatasetRecordsFromExplorer(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null postDatasetRecordsFromFile(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null postDatasetRecordsFromPlayground(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null provisionAdminGroupToWorkspace(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null queryWorkflowExecution(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null registerConfig(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null registerDeployment(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null removeAdminGroupFromWorkspace(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null removeAdminUsersFromGroup(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null removeAdminUsersWorkspaces(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null reprocessLibraryDocument(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null resetWorkflow(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null restartConversation(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null restartConversationStream(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null restartDeployment(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null resumeSchedule(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveAdminGroupWorkspaceAssignments(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveAdminNestedGroupsAdmin(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveAdminScimSyncRun(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveAdminUser(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveAdminUserGroup(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveAdminUserGroupMembers(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveAgent(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveAgentVersion(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveBatchJob(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveConnector(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveConversation(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveFile(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveFileSignedUrl(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveLibrary(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveLibraryDocument(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveLibraryDocumentExtractedTextSignedUrl(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveLibraryDocumentSignedUrl(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveLibraryDocumentStatus(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveLibraryDocumentTextContent(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveModel(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrievePrompt(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrievePromptVersion(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveSkill(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveSkillVersion(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null scheduleWorkflow(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null searchLatestSpanEvaluations(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null searchLogs(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null searchSpanEvaluations(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null searchSpans(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null searchTraces(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null setAdminNestedGroupsAdmin(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null shareConnector(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null signalWorkflowExecution(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null startConversation(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null startConversationStream(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null startDeployment(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null stopDeployment(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null streamDeploymentLogs(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null streamWorkflowExecution(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null streamWorkflowExecutionLogs(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null terminateWorkflowExecution(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null triggerAdminScimSync(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null triggerSchedule(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null unarchiveModel(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null unarchiveWorkflow(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null unregisterDeployment(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null unscheduleWorkflow(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null unshareConnector(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateAdminGroupWorkspaceAssignment(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateAdminSpendLimits(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateAdminUser(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateAdminUserGroup(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateAdminUserGroupOrganizationRole(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateAdminWorkspaces(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateAgent(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateAgentVersion(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateConnector(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateDataset(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateDatasetRecordPayload(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateDatasetRecordProperties(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateDeployment(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateFineTunedModel(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateIndexMetrics(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateJudge(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateLibrary(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateLibraryDocument(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updatePrompt(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updatePromptVersionMetadata(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateRunInfo(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateSchedule(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateSkill(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateSkillVersionMetadata(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateVoice(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateWorkflow(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateWorkflowExecution(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null uploadFile(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null uploadLibraryDocument(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null upsertAgentVersionAlias(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null upsertLibraryShare(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null usageAdminByAgent(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null usageAdminByOrganization(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null usageAdminByUser(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null usageAdminByWorkspace(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null usageAdminOverTime(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * API METHODS END
 */
class MistralAI
{
    /** @var list<string> */
    private const DEFAULT_FILE_FIELDS = [
        'file',
        'files',
    ];

    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly UriFactoryInterface $uriFactory,
        private readonly ClientInterface $httpClient,
        #[SensitiveParameter]
        private readonly string $apiKey,
        private readonly string $origin = '',
        private readonly string $apiVersion = '',
    ) {}

    /**
     * Calls a registered endpoint by its SDK method name.
     *
     * @param array<int, mixed> $args
     *
     * @throws MistralAIException If the API returns an error.
     * @throws InvalidArgumentException If the endpoint or its arguments are invalid.
     * @throws RandomException If multipart boundary generation fails.
     * @throws Throwable If request body construction or streaming fails.
     */
    public function __call(string $key, array $args): ?ResponseInterface
    {
        $endpoint = MistralAIURLBuilder::getEndpoint($key);
        [$parameters, $body, $streamCallback, $customHeaders] = $this->extractCallArguments($args, $endpoint);

        return $this->request($key, $parameters, $body, $streamCallback, $customHeaders);
    }

    /**
     * Sends a request using a registered endpoint name.
     *
     * @param array<string, mixed> $parameters Path and query parameters.
     * @param array<string, mixed> $body JSON or multipart body fields.
     * @param callable(array<string, mixed>):void|null $streamCallback SSE event callback.
     * @param array<string, string|string[]> $customHeaders Additional request headers.
     *
     * @throws MistralAIException If the API returns an error.
     * @throws InvalidArgumentException If the endpoint or its parameters are invalid.
     * @throws RandomException If multipart boundary generation fails.
     * @throws Throwable If request body construction or streaming fails.
     */
    public function request(
        string $key,
        array $parameters = [],
        array $body = [],
        ?callable $streamCallback = null,
        array $customHeaders = [],
    ): ?ResponseInterface {
        $endpoint = MistralAIURLBuilder::getEndpoint($key);
        $pathParameters = $this->getPathParameters($endpoint['path']);
        $pathKeys = \array_flip($pathParameters);
        $pathValues = \array_intersect_key($parameters, $pathKeys);
        $query = $endpoint['query'] ?? [];

        foreach (\array_diff_key($parameters, $pathKeys) as $name => $value) {
            $query[$name] = $value;
        }
        $uri = MistralAIURLBuilder::createUrl(
            $this->uriFactory,
            $key,
            $pathValues,
            $this->origin,
            $this->apiVersion,
        );

        if ($query !== []) {
            $uri = $uri->withQuery(\http_build_query($query, '', '&', PHP_QUERY_RFC3986));
        }

        return $this->sendRequest(
            $uri,
            $endpoint['method'],
            $endpoint,
            $body,
            $streamCallback,
            $customHeaders,
        );
    }

    /**
     * Normalizes the legacy two-array convention and the canonical body-first convention.
     *
     * @param array<int, mixed> $args
     * @param array{method:string,path:string,body?:string,fileFields?:list<string>,headers?:array<string,string|string[]>,streaming?:bool}|null $endpoint
     *
     * @return array{array<string,mixed>,array<string,mixed>,callable|null,array<string,string|string[]>}
     *
     * @throws InvalidArgumentException If the endpoint arguments are invalid.
     */
    private function extractCallArguments(array $args, ?array $endpoint = null): array
    {
        if (\count($args) > 3) {
            throw new InvalidArgumentException('Endpoint calls accept at most three arguments.');
        }

        if (isset($args[0]) && !\is_array($args[0])) {
            throw new InvalidArgumentException('First argument must be an array of parameters.');
        }

        $first = $args[0] ?? [];
        $second = [];
        $hasSecondArray = isset($args[1]) && \is_array($args[1]);
        $streamCallback = null;

        if ($hasSecondArray) {
            $second = $args[1];
        } elseif (isset($args[1])) {
            if (!\is_callable($args[1])) {
                throw new InvalidArgumentException('Second argument must be an array or callable.');
            }

            $streamCallback = $args[1];
        }

        if (isset($args[2])) {
            if (!$hasSecondArray || !\is_callable($args[2])) {
                throw new InvalidArgumentException('Third argument must be a stream callback.');
            }

            $streamCallback = $args[2];
        }

        $customHeaders = $this->extractCustomHeaders($first, $second);

        // Keep the old helper shape usable by reflective consumers.
        if ($endpoint === null) {
            return [$first, $second, $streamCallback, $customHeaders];
        }

        $bodyType = $endpoint['body'] ?? $this->inferBodyType($endpoint['method'], $endpoint['path']);

        if ($bodyType === 'none') {
            return [$first + $second, [], $streamCallback, $customHeaders];
        }

        // Existing 3.x calls pass path/query parameters first and the body second.
        if ($hasSecondArray) {
            return [$first, $second, $streamCallback, $customHeaders];
        }

        $pathParameters = $this->getPathParameters($endpoint['path']);

        if ($pathParameters === []) {
            return [[], $first, $streamCallback, $customHeaders];
        }

        $pathKeys = \array_flip($pathParameters);

        return [
            \array_intersect_key($first, $pathKeys),
            \array_diff_key($first, $pathKeys),
            $streamCallback,
            $customHeaders,
        ];
    }

    /**
     * @param array<string, mixed> $first
     * @param array<string, mixed> $second
     *
     * @return array<string, string|string[]>
     *
     * @throws InvalidArgumentException If customHeaders is not an array.
     */
    private function extractCustomHeaders(array &$first, array &$second): array
    {
        $headers = [];

        foreach ([$first, $second] as $values) {
            if (!isset($values['customHeaders'])) {
                continue;
            }

            if (!\is_array($values['customHeaders'])) {
                throw new InvalidArgumentException('customHeaders must be an array.');
            }

            foreach ($values['customHeaders'] as $name => $value) {
                $headers[$name] = $value;
            }
        }

        unset($first['customHeaders'], $second['customHeaders']);

        return $headers;
    }

    /**
     * @return list<string>
     */
    private function getPathParameters(string $path): array
    {
        \preg_match_all('/\{([A-Za-z_]\w*)}/', $path, $matches);

        return $matches[1];
    }

    private function inferBodyType(string $method, string $path): string
    {
        if (\in_array($method, ['GET', 'DELETE'], true)) {
            return 'none';
        }

        if (\in_array($path, [
            '/audio/transcriptions',
            '/files',
            '/libraries/{library_id}/documents',
        ], true)) {
            return 'multipart';
        }

        return 'json';
    }

    /**
     * @param array{path?:string,body?:string,fileFields?:list<string>,headers?:array<string,string|string[]>,streaming?:bool} $endpoint
     * @param array<string, mixed> $body
     * @param array<string, string|string[]> $customHeaders
     *
     * @throws MistralAIException If the API returns an error.
     * @throws RandomException If multipart boundary generation fails.
     * @throws Throwable If request body construction or streaming fails.
     */
    private function sendRequest(
        UriInterface $uri,
        string $method,
        array $endpoint,
        array $body,
        ?callable $streamCallback,
        array $customHeaders,
    ): ResponseInterface {
        $bodyType = $endpoint['body'] ?? $this->inferBodyType($method, $endpoint['path'] ?? $uri->getPath());
        $boundary = $body !== [] && $bodyType === 'multipart' ? $this->generateMultipartBoundary() : null;
        $requestBody = $this->createRequestBody($bodyType, $body, $boundary, $endpoint['fileFields'] ?? []);
        $contentType = null;

        if ($requestBody !== null) {
            $contentType = $bodyType === 'multipart'
                ? "multipart/form-data; boundary={$boundary}"
                : 'application/json';
        }

        $headers = $endpoint['headers'] ?? [];

        foreach ($customHeaders as $name => $value) {
            $headers[$name] = $value;
        }

        $request = $this->requestFactory->createRequest($method, $uri);
        $request = $this->applyHeaders($request, $this->createHeaders($contentType, null, $headers));

        if ($requestBody !== null) {
            $request = $request->withBody($requestBody);
        }

        $isStreamingRequest = ($endpoint['streaming'] ?? false)
            || ($body['stream'] ?? false) === true
            || ($body['stream_format'] ?? null) === 'sse';

        try {
            $response = $isStreamingRequest && $this->httpClient instanceof StreamingClientInterface
                ? $this->httpClient->sendStreamingRequest($request)
                : $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            throw new MistralAIException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        $this->throwForErrorResponse($response);

        $isEventStream = \str_contains(\strtolower($response->getHeaderLine('Content-Type')), 'text/event-stream');
        $streamRequested = $isStreamingRequest;

        if ($streamCallback !== null && ($streamRequested || $isEventStream)) {
            (new ServerSentEventDecoder())->decode($response->getBody(), $streamCallback);
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $body
     * @param list<string> $fileFields
     *
     * @throws MistralAIException If JSON encoding fails.
     * @throws RandomException Retained for compatibility with the 3.x exception contract.
     * @throws Throwable If multipart body construction fails.
     */
    private function createRequestBody(
        string $bodyType,
        array $body,
        ?string $boundary,
        array $fileFields,
    ): ?StreamInterface {
        if ($body === [] || $bodyType === 'none') {
            return null;
        }

        if ($bodyType === 'multipart') {
            return $this->createMultipartStream($body, (string) $boundary, $fileFields);
        }

        return $this->streamFactory->createStream($this->createJsonBody($body));
    }

    /**
     * @throws MistralAIException If the API returns an error response.
     */
    private function throwForErrorResponse(ResponseInterface $response): void
    {
        if ($response->getStatusCode() < 400) {
            return;
        }

        throw new MistralAIException(
            $response->getBody()->getContents(),
            $response->getStatusCode(),
            null,
            $response->getHeaderLine('x-request-id'),
            $response->getHeaders(),
        );
    }

    /**
     * @throws Exception If the operating system cannot provide random bytes.
     * @throws RandomException Retained for compatibility with the 3.x exception contract.
     */
    private function generateMultipartBoundary(): string
    {
        return '----MistralAI' . \bin2hex(\random_bytes(16));
    }

    /**
     * The bool form is retained for backwards compatibility with reflective test helpers.
     *
     * @param bool|string|null $contentType
     * @param string|null $boundary
     * @param array<string, string|string[]> $customHeaders
     * @return array<string, string|string[]>
     */
    private function createHeaders(
        bool|string|null $contentType,
        ?string $boundary = null,
        array $customHeaders = [],
    ): array {
        if (\is_bool($contentType)) {
            $contentType = $contentType
                ? "multipart/form-data; boundary={$boundary}"
                : 'application/json';
        }

        $headers = ['Authorization' => 'Bearer ' . $this->apiKey];

        if ($contentType !== null) {
            $headers['Content-Type'] = $contentType;
        }

        return \array_replace($headers, $customHeaders);
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    private function applyHeaders(RequestInterface $request, array $headers): RequestInterface
    {
        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        return $request;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @throws MistralAIException If JSON encoding fails.
     */
    private function createJsonBody(array $params): string
    {
        try {
            return \json_encode($params, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new MistralAIException('JSON encode error: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @param array<string, mixed> $params
     * @param list<string> $fileFields
     *
     * @throws RandomException Retained for compatibility with the 3.x exception contract.
     * @throws Throwable If multipart body construction fails.
     */
    private function createMultipartStream(array $params, string $boundary, array $fileFields = []): StreamInterface
    {
        if ($fileFields === []) {
            $fileFields = self::DEFAULT_FILE_FIELDS;
        }

        return (new MultipartBodyBuilder($this->streamFactory))->build($params, $boundary, $fileFields);
    }
}
