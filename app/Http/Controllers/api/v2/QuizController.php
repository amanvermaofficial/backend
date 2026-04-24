<?php

namespace App\Http\Controllers\api\v2;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Services\QuizService;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    protected $quizService;


    public function __construct(QuizService $quizService)
    {
        $this->quizService = $quizService;
    }

    public function show(Quiz $quiz,Request $request)
    {
       $lang = $request->get('lang', 'en');
       $data = $this->quizService->show($quiz,$lang);
       return $this->successResponse('Quiz fetched successfully', $data);
    }
}
