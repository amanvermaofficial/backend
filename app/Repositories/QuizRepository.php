<?php

namespace App\Repositories;

use App\Models\CourseTrade;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;

class QuizRepository
{
    public function getCourseTrade($courseId, $tradeId)
    {
        return CourseTrade::where('course_id', $courseId)
            ->where('trade_id', $tradeId)
            ->firstOrFail();
    }

    public function getQuizzesByCourseTrade($courseTradeId)
    {
        $studentId = auth('sanctum')->id();

        return Quiz::where('is_active', true)
            ->whereHas('courseTrades', function ($q) use ($courseTradeId) {
                $q->where('course_trade_id', $courseTradeId);
            })

            ->withCount('questions')

            // total attempts count
            ->withCount([
                'attempts as attempts_count' => function ($q) use ($studentId) {
                    $q->where('student_id', $studentId);
                }
            ])

            // latest attempt
            ->with([
                'attempts' => function ($q) use ($studentId) {
                    $q->where('student_id', $studentId)
                        ->latest()
                        ->limit(1);
                }
            ])

            ->get();
    }


    public function getQuizWithQuestions($quizId)
    {
        return Quiz::with('questions.options')->find($quizId);
    }

    public function createAttempt($studentId, $quizId, $totalQuestions, $startTime, $endTime, $attemptNumber)
    {
        return QuizAttempt::create([
            'student_id' => $studentId,
            'quiz_id' => $quizId,
            'attempt_number' => $attemptNumber,
            'total_questions' => $totalQuestions,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => abs($endTime->diffInSeconds($startTime)),
        ]);
    }

    public function getQuestion($questionId)
    {
        return Question::find($questionId);
    }

    public function getOption($optionId)
    {
        return Option::find($optionId);
    }

    public function getCorrectOption($questionId)
    {
        return Option::where('question_id', $questionId)
            ->where('is_correct', true)
            ->first();
    }

    public function saveAttemptAnswer($attemptId, $question, $selectedOption, $correctOption, $isCorrect)
    {
        return QuizAttemptAnswer::create([
            'quiz_attempt_id' => $attemptId,
            'question_id' => $question->id,
            'selected_option_id' => $selectedOption?->id,
            'correct_option_id' => $correctOption?->id,
            'is_correct' => $isCorrect,
        ]);
    }

    public function updateAttemptResult($attempt, $score, $correct, $wrong, $skipped)
    {
        return $attempt->update([
            'score' => $score,
            'correct_answers' => $correct,
            'wrong_answers' => $wrong,
            'skipped_questions' => $skipped,
            'end_time' => now(),
        ]);
    }


    public function findActiveAttempt($studentId, $quizId)
    {
        return QuizAttempt::where('student_id', $studentId)
            ->where('quiz_id', $quizId)
            ->latest()
            ->first();
    }

    public function findAttempt($studentId, $quizId)
    {
        return QuizAttempt::where('student_id', $studentId)
            ->where('quiz_id', $quizId)
            ->first();
    }

    public function findCompletedAttempt($studentId, $quizId)
    {
        return QuizAttempt::where('student_id', $studentId)
            ->where('quiz_id', $quizId)
            ->whereNotNull('score')
            ->latest()
            ->first();
    }

    public function getAttemptAnswers($attemptId)
    {
        return QuizAttemptAnswer::where('quiz_attempt_id', $attemptId)
            ->get([
                'question_id',
                'selected_option_id',
                'correct_option_id',
                'is_correct'
            ]);
    }

    public function getLastAttempt($studentId, $quizId)
    {
        return QuizAttempt::where('student_id', $studentId)
            ->where('quiz_id', $quizId)
            ->latest()
            ->first();
    }

    public function getPreviousAttempts($studentId, $quizId)
    {
        return QuizAttempt::where('student_id', $studentId)
            ->where('quiz_id', $quizId)
            ->latest()
            ->take(3)
            ->get()
            ->map(function ($attempt) {

                return [
                    'id' => $attempt->id,
                    'attempt_number' => $attempt->attempt_number,
                    'score' => $attempt->score,
                    'total_questions' => $attempt->total_questions,
                    'date' => $attempt->created_at->format('d M Y'),
                ];
            });
    }
}
