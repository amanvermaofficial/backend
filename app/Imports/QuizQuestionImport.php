<?php

namespace App\Imports;

use App\Models\Option;
use App\Models\Question;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QuizQuestionImport implements ToCollection, WithHeadingRow
{
    protected $quizId;
    protected $inserted = 0;

    public function __construct($quizId)
    {
        $this->quizId = $quizId;
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            if (Question::where('quiz_id', $this->quizId)->exists()) {
                throw new Exception('Quiz already contains questions');
            }

            foreach ($rows as $index => $row) {

                if (empty($row['question'])) {
                    continue;
                }

                $row = $row->map(fn($v) => is_string($v) ? trim($v) : $v);

                // Duplicate check (English)
                if (
                    Question::where('quiz_id', $this->quizId)
                    ->where('question_text', $row['question'])
                    ->exists()
                ) {
                    continue;
                }

                // Options
                $options = [
                    'A' => [
                        'en' => $row['option_a'] ?? null,
                       'hi' => !empty($row['option_a_hi']) ? $row['option_a_hi'] : ($row['option_a'] ?? null),
                    ],
                    'B' => [
                        'en' => $row['option_b'] ?? null,
                        'hi' => !empty($row['option_b_hi']) ? $row['option_b_hi'] : ($row['option_b'] ?? null),
                    ],
                    'C' => [
                        'en' => $row['option_c'] ?? null,
                        'hi' => !empty($row['option_c_hi']) ? $row['option_c_hi'] : ($row['option_c'] ?? null),
                    ],
                    'D' => [
                        'en' => $row['option_d'] ?? null,
                        'hi' => !empty($row['option_d_hi']) ? $row['option_d_hi'] : ($row['option_d'] ?? null),
                    ],
                ];

                // Validation
                foreach ($options as $opt) {
                    if (empty($opt['en'])) {
                        throw new Exception('Invalid option at row ' . ($index + 2));
                    }
                }

                if (!in_array($row['correct_option'], ['A', 'B', 'C', 'D'])) {
                    throw new Exception('Correct option must be A/B/C/D at row ' . ($index + 2));
                }

                // Create Question
                $question = Question::create([
                    'quiz_id' => $this->quizId,
                    'question_text' => $row['question'],
                     'question_hi' => !empty($row['question_hi']) ? $row['question_hi'] : $row['question'],
                    'solution' => $row['solution'] ?? null,
                ]);

                // Create Options
                foreach ($options as $key => $opt) {
                    Option::create([
                        'question_id' => $question->id,
                        'option_text' => $opt['en'],
                        'option_hi' => $opt['hi'],
                        'is_correct' => ($row['correct_option'] === $key),
                    ]);
                }

                $this->inserted++;
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getInsertedCount(): int
    {
        return $this->inserted;
    }
}
