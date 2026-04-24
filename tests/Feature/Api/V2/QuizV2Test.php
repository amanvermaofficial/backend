<?php

namespace Tests\Feature\Api\V2;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class QuizV2Test extends TestCase
{
    use RefreshDatabase;

    protected function createQuizWithTranslations()
    {
        $quiz = Quiz::factory()->create();

        $question = Question::factory()->create([
            'quiz_id' => $quiz->id,
            'question_text' => 'What is PHP?',
            'question_hi'   => 'PHP क्या है?',
        ]);

        Option::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'Language',
            'option_hi'   => 'भाषा',
        ]);

        Option::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'Framework',
            'option_hi'   => 'फ्रेमवर्क',
        ]);

        return $quiz;
    }

    public function test_it_returns_quiz_in_english_by_default()
    {
        $quiz = $this->createQuizWithTranslations();
        $response = $this->getJson("/api/v2/quiz/{$quiz->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'question',
                        'options' => [
                            '*' => [
                                'key',
                                'text'
                            ]
                        ]
                    ]
                ]
            ])
            ->assertJsonFragment([
                'question' => 'What is PHP?',
            ]);
    }

    public function test_it_returns_quiz_in_hindi_when_lang_hi()
    {
        $quiz = $this->createQuizWithTranslations();

        $response = $this->getJson("/api/v2/quiz/{$quiz->id}?lang=hi");
        $response->assertStatus(200)
            ->assertJsonFragment([
                'question' => 'PHP क्या है?',
            ]);
    }

    public function test_it_falls_back_to_english_if_hindi_not_available()
    {
        $quiz = Quiz::factory()->create();

        $question = Question::factory()->create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Fallback Question',
            'question_hi'   => null,
        ]);

        Option::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'Option 1',
            'option_hi'   => null,
        ]);

        $response = $this->getJson("/api/v2/quiz/{$quiz->id}?lang=hi");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'question' => 'Fallback Question',
            ]);
    }
}
