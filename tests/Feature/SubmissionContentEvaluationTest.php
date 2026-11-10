<?php

namespace Tests\Feature;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubmissionContentEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitters_can_create_social_posts_and_website_articles(): void
    {
        $submitter = User::factory()->org()->create();

        $socialPost = $this->actingAs($submitter)
            ->postJson('/api/submissions', [
                'original_caption' => 'A concise event update for the university community.',
                'review_destination' => 'pair',
                'links' => [''],
                'media' => [null],
            ])
            ->assertCreated()
            ->json('data');

        $article = $this->actingAs($submitter)
            ->postJson('/api/submissions', [
                'submission_type' => Submission::TYPE_WEBSITE_ARTICLE,
                'article_title' => 'University announces its annual research forum',
                'original_caption' => str_repeat('The university shared details about its annual research forum. ', 3),
                'review_destination' => 'pair',
            ])
            ->assertCreated()
            ->json('data');

        $this->assertSame(Submission::TYPE_SOCIAL_MEDIA, $socialPost['submission_type']);
        $this->assertNull($socialPost['article_title']);
        $this->assertSame([], $socialPost['links']);
        $this->assertSame(Submission::TYPE_WEBSITE_ARTICLE, $article['submission_type']);
        $this->assertSame('University announces its annual research forum', $article['article_title']);
    }

    public function test_submitters_can_generate_an_article_draft_without_creating_a_submission(): void
    {
        $submitter = User::factory()->org()->create();
        $headline = 'University hosts annual research forum';
        $body = trim(str_repeat('The university hosted a research forum for students and faculty. ', 3));

        config([
            'services.openai.key' => 'test-key',
            'services.openai.model' => 'gpt-test',
        ]);
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['headline' => $headline, 'body' => $body]),
                    ],
                ]],
            ]),
        ]);

        $this->actingAs($submitter)
            ->postJson('/api/submissions/generate-draft', [
                'submission_type' => Submission::TYPE_WEBSITE_ARTICLE,
                'prompt' => 'The university hosted a research forum for students and faculty.',
                'llm_provider' => 'openai',
                'tone' => 'professional',
            ])
            ->assertOk()
            ->assertJsonPath('data.submission_type', Submission::TYPE_WEBSITE_ARTICLE)
            ->assertJsonPath('data.article_title', $headline)
            ->assertJsonPath('data.original_caption', $body);

        $this->assertDatabaseCount('submissions', 0);
        Http::assertSent(fn (ClientRequest $request) => str_contains(
            $request->data()['messages'][0]['content'],
            'Do not invent dates'
        ) && $request->data()['messages'][1]['content'] === 'The university hosted a research forum for students and faculty.');
    }

    public function test_ollama_drafts_social_posts_and_articles_using_the_submission_guardrails(): void
    {
        $submitter = User::factory()->org()->create();
        $articleBody = trim(str_repeat(
            'The university held a research forum for students and faculty to share verified project updates. ',
            3
        ));

        config([
            'services.ollama.base_url' => 'http://ollama.test/',
            'services.ollama.model' => 'qwen3:8b',
        ]);
        Http::fake([
            'ollama.test/api/chat' => Http::sequence()
                ->push([
                    'message' => [
                        'content' => json_encode([
                            'headline' => '',
                            'body' => 'Students and faculty gathered for a university research forum.',
                        ]),
                    ],
                ])
                ->push([
                    'message' => [
                        'content' => json_encode([
                            'headline' => 'University hosts research forum',
                            'body' => $articleBody,
                        ]),
                    ],
                ]),
        ]);

        $socialDraft = $this->actingAs($submitter)
            ->postJson('/api/submissions/generate-draft', [
                'submission_type' => Submission::TYPE_SOCIAL_MEDIA,
                'prompt' => 'The university research forum took place on campus today.',
                'llm_provider' => 'ollama',
                'tone' => 'professional',
            ])
            ->assertOk()
            ->assertJsonPath('data.submission_type', Submission::TYPE_SOCIAL_MEDIA)
            ->assertJsonPath('data.original_caption', 'Students and faculty gathered for a university research forum.')
            ->json('data');

        $this->assertSame('ollama', $socialDraft['provider_used']);

        $this->postJson('/api/submissions/generate-draft', [
            'submission_type' => Submission::TYPE_WEBSITE_ARTICLE,
            'prompt' => 'The university research forum took place on campus today.',
            'llm_provider' => 'ollama',
            'tone' => 'formal',
        ])
            ->assertOk()
            ->assertJsonPath('data.submission_type', Submission::TYPE_WEBSITE_ARTICLE)
            ->assertJsonPath('data.article_title', 'University hosts research forum')
            ->assertJsonPath('data.original_caption', $articleBody)
            ->assertJsonPath('data.provider_used', 'ollama');

        Http::assertSentCount(2);
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'http://ollama.test/api/chat'
            && $request['model'] === 'qwen3:8b'
            && $request['stream'] === false
            && $request['format'] === 'json'
            && $request['options']['num_predict'] === 900
            && str_contains($request['messages'][0]['content'], 'Do not invent dates')
            && $request['messages'][1]['content'] === 'The university research forum took place on campus today.');
        Http::assertSent(fn (ClientRequest $request) => $request['options']['num_predict'] === 4000
            && str_contains($request['messages'][0]['content'], 'detailed multi-paragraph body')
            && $request['messages'][1]['content'] === 'The university research forum took place on campus today.');

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_ollama_unavailable_keeps_manual_drafting_available(): void
    {
        $submitter = User::factory()->org()->create();
        config([
            'services.ollama.base_url' => 'http://ollama.test',
            'services.ollama.model' => 'qwen3:8b',
        ]);
        Http::fake([
            'ollama.test/api/chat' => Http::response(['error' => 'model not running'], 503),
        ]);

        $this->actingAs($submitter)
            ->postJson('/api/submissions/generate-draft', [
                'submission_type' => Submission::TYPE_SOCIAL_MEDIA,
                'prompt' => 'A few confirmed details about the event.',
                'llm_provider' => 'ollama',
                'tone' => 'formal',
            ])
            ->assertServiceUnavailable()
            ->assertJsonPath('fallback', true)
            ->assertJsonPath('message', 'AI drafting is unavailable right now. You can write your submission manually.');

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_ollama_request_extends_the_php_execution_limit_only_for_its_request(): void
    {
        $submitter = User::factory()->org()->create();
        $originalExecutionLimit = (int) ini_get('max_execution_time');
        config([
            'services.ollama.base_url' => 'http://ollama.test',
            'services.ollama.model' => 'qwen3:8b',
            'services.ollama.timeout' => 90,
        ]);
        Http::fake([
            'ollama.test/api/chat' => Http::response([
                'message' => [
                    'content' => json_encode([
                        'headline' => '',
                        'body' => 'Students attended the university community research forum today.',
                    ]),
                ],
            ]),
        ]);

        try {
            $this->actingAs($submitter)
                ->postJson('/api/submissions/generate-draft', [
                    'submission_type' => Submission::TYPE_SOCIAL_MEDIA,
                    'prompt' => 'The university community research forum took place today.',
                    'llm_provider' => 'ollama',
                    'tone' => 'professional',
                ])
                ->assertOk();

            $this->assertSame(105, (int) ini_get('max_execution_time'));
        } finally {
            set_time_limit($originalExecutionLimit);
        }
    }

    public function test_submitters_can_continue_manually_when_ai_drafting_is_unavailable(): void
    {
        $submitter = User::factory()->org()->create();
        config(['services.openai.key' => null]);

        $this->actingAs($submitter)
            ->postJson('/api/submissions/generate-draft', [
                'submission_type' => Submission::TYPE_SOCIAL_MEDIA,
                'prompt' => 'A few confirmed details about the event.',
                'llm_provider' => 'openai',
                'tone' => 'formal',
            ])
            ->assertServiceUnavailable()
            ->assertJsonPath('fallback', true);

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_website_articles_require_a_headline_and_minimum_body_length(): void
    {
        $submitter = User::factory()->org()->create();

        $this->actingAs($submitter)
            ->postJson('/api/submissions', [
                'submission_type' => Submission::TYPE_WEBSITE_ARTICLE,
                'original_caption' => 'Too short for an article.',
                'review_destination' => 'pair',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['article_title', 'original_caption']);
    }

    public function test_social_media_review_shows_a_facebook_page_preview(): void
    {
        $submitter = User::factory()->org()->create();
        $reviewer = User::factory()->pair()->create();
        $submission = Submission::factory()->submitted()->create([
            'user_id' => $submitter->id,
            'submission_type' => Submission::TYPE_SOCIAL_MEDIA,
            'original_caption' => 'Campus event preview text for the Facebook Page.',
            'links' => null,
            'media_paths' => null,
            'review_department_id' => null,
        ]);

        $this->actingAs($reviewer)
            ->get(route('dashboard.submissions.review', $submission))
            ->assertOk()
            ->assertSee('Facebook Page preview')
            ->assertSee('Campus event preview text for the Facebook Page.')
            ->assertSee('Like')
            ->assertSee('Share');
    }

    public function test_submit_and_reviewer_pages_distinguish_website_articles(): void
    {
        $submitter = User::factory()->org()->create();
        $reviewer = User::factory()->pair()->create();
        $submission = Submission::factory()->submitted()->create([
            'user_id' => $submitter->id,
            'submission_type' => Submission::TYPE_WEBSITE_ARTICLE,
            'article_title' => 'A detailed university story',
            'original_caption' => str_repeat('The university shared verified information with its community. ', 3),
            'review_department_id' => null,
        ]);

        $this->actingAs($submitter)
            ->get(route('org.submit'))
            ->assertOk()
            ->assertSee('What are you submitting?')
            ->assertSee('USJR website article')
            ->assertSee('Ollama (local)')
            ->assertSee('draftPrompt')
            ->assertSee('Generate draft')
            ->assertSee('article_title');

        $this->actingAs($reviewer)
            ->get(route('dashboard.submissions.review', $submission))
            ->assertOk()
            ->assertSee('A detailed university story')
            ->assertSee('Original article body')
            ->assertSee('USJR website article')
            ->assertSee('Website article preview');

        $this->actingAs($reviewer)
            ->get(route('dashboard.submissions.evaluate', $submission))
            ->assertOk()
            ->assertSee('Reviewed headline')
            ->assertSee('Reviewed article body')
            ->assertSee('Verify names, dates, and claims')
            ->assertSee('Website article preview')
            ->assertSee('data-preview-body-for="reviewBody"', false)
            ->assertSee('data-preview-title-for="reviewTitle"', false);

        $this->actingAs($reviewer)
            ->get(route('dashboard.submissions.review', $submission))
            ->assertOk()
            ->assertSee('href="'.route('dashboard.submissions.evaluate', $submission).'"', false)
            ->assertSee('Evaluate');

        $this->actingAs($reviewer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('dashboard.submissions.review', $submission).'"', false)
            ->assertSee('Review');
    }

    public function test_manual_article_review_saves_an_edited_headline_and_body(): void
    {
        $reviewer = User::factory()->pair()->create();
        $submitter = User::factory()->org()->create();
        $submission = Submission::factory()->submitted()->create([
            'user_id' => $submitter->id,
            'submission_type' => Submission::TYPE_WEBSITE_ARTICLE,
            'article_title' => 'Original headline',
            'original_caption' => str_repeat('The submitted article describes confirmed university news. ', 3),
            'review_department_id' => null,
        ]);
        $reviewedBody = str_repeat('The university confirmed details and provided useful context for readers. ', 3);

        $response = $this->actingAs($reviewer)
            ->postJson("/api/submissions/{$submission->id}/save-manual-caption", [
                'manual_title' => 'Reviewed headline',
                'manual_caption' => $reviewedBody,
                'pair_feedback' => 'Clarified the audience and retained the supplied facts.',
            ]);

        $this->assertSame(200, $response->status(), $response->getContent());
        $response
            ->assertJsonPath('data.enhanced_title', 'Reviewed headline')
            ->assertJsonPath('data.workflow_status', 'under_peer_review');

        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'enhanced_title' => 'Reviewed headline',
            'enhanced_caption' => trim($reviewedBody),
        ]);
    }

    public function test_manual_article_review_rejects_missing_headline_or_short_body(): void
    {
        $reviewer = User::factory()->pair()->create();
        $submitter = User::factory()->org()->create();
        $submission = Submission::factory()->submitted()->create([
            'user_id' => $submitter->id,
            'submission_type' => Submission::TYPE_WEBSITE_ARTICLE,
            'article_title' => 'Original headline',
            'review_department_id' => null,
        ]);

        $this->actingAs($reviewer)
            ->postJson("/api/submissions/{$submission->id}/save-manual-caption", [
                'manual_caption' => 'A short body.',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['manual_title', 'manual_caption']);
    }

    public function test_ai_article_enhancement_uses_article_specific_guardrails_and_prompt_context(): void
    {
        $reviewer = User::factory()->pair()->create();
        $submitter = User::factory()->org()->create();
        $submission = Submission::factory()->submitted()->create([
            'user_id' => $submitter->id,
            'submission_type' => Submission::TYPE_WEBSITE_ARTICLE,
            'article_title' => 'Community research forum',
            'original_caption' => str_repeat('The forum will take place on the date provided by the organizers. ', 3),
            'review_department_id' => null,
            'enhanced_caption' => null,
        ]);
        $draft = str_repeat('The university provided context and confirmed details for its community research forum. ', 3);

        config([
            'services.openai.key' => 'test-key',
            'services.openai.model' => 'gpt-test',
        ]);
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => $draft]]],
            ]),
        ]);

        $this->actingAs($reviewer)
            ->postJson("/api/submissions/{$submission->id}/enhance", [
                'llm_provider' => 'openai',
                'tone' => 'professional',
            ])
            ->assertOk()
            ->assertJsonPath('data.submission_type', Submission::TYPE_WEBSITE_ARTICLE)
            ->assertJsonPath('data.enhanced_title', 'Community research forum')
            ->assertJsonPath('data.enhanced_caption', trim($draft));

        $this->assertSame('submitted', $submission->fresh()->workflow_status);
        $this->assertNull($submission->fresh()->enhanced_caption);
        $this->assertCount(0, $submitter->notifications);

        Http::assertSent(function (ClientRequest $request): bool {
            $systemPrompt = $request->data()['messages'][0]['content'] ?? '';
            $userContent = $request->data()['messages'][1]['content'] ?? '';

            return $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request->data()['model'] === 'gpt-test'
                && str_contains($systemPrompt, 'official University of San Jose-Recoletos website')
                && str_contains($systemPrompt, 'Do not invent facts')
                && str_contains($systemPrompt, 'untrusted source material')
                && str_contains($userContent, 'Headline (keep unchanged):')
                && str_contains($userContent, 'Article body:')
                && ($request->data()['max_completion_tokens'] ?? null) === 3000;
        });
    }

    public function test_ai_enhancement_reports_unavailable_provider_without_hiding_manual_review(): void
    {
        $reviewer = User::factory()->pair()->create();
        $submitter = User::factory()->org()->create();
        $submission = Submission::factory()->submitted()->create([
            'user_id' => $submitter->id,
            'review_department_id' => null,
            'enhanced_caption' => null,
        ]);
        config(['services.openai.key' => null]);
        Http::fake();

        $this->actingAs($reviewer)
            ->postJson("/api/submissions/{$submission->id}/enhance", ['llm_provider' => 'openai'])
            ->assertStatus(202)
            ->assertJsonPath('fallback', true)
            ->assertJsonPath('success', false);

        Http::assertNothingSent();
        $this->assertNull($submission->fresh()->enhanced_caption);
    }
}
