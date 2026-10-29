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
        $this->assertSame(Submission::TYPE_WEBSITE_ARTICLE, $article['submission_type']);
        $this->assertSame('University announces its annual research forum', $article['article_title']);
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
            ->assertSee('article_title');

        $this->actingAs($reviewer)
            ->get(route('dashboard.submissions.review', $submission))
            ->assertOk()
            ->assertSee('A detailed university story')
            ->assertSee('Original article body')
            ->assertSee('USJR website article');

        $this->actingAs($reviewer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-submission-type="website_article"', false)
            ->assertSee('Reviewed article headline');
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
                && str_contains($userContent, 'Article body:');
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
