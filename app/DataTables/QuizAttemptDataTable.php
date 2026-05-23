<?php

namespace App\DataTables;

use App\Models\QuizAttempt;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class QuizAttemptDataTable extends DataTable
{
    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()

            ->addColumn('student_name', function ($row) {
                return $row->student?->name ?? '-';
            })

            ->addColumn('quiz_title', function ($row) {
                return $row->quiz?->title ?? '-';
            })

            ->editColumn(
                'score',
                fn($row) =>
                $row->score . ' / ' . $row->total_questions
            )

            ->addColumn('actions', function ($row) {
                $viewUrl = route('admin.quiz-attempts.show', $row->id);

                $buttons = '';

                if (auth()->user()->can('users.edit')) {
                    $buttons .= '
            <a href="' . $viewUrl . '" class="btn btn-sm btn-info">
                <i class="fas fa-edit"></i>
            </a>';
                }

                return '<div class="btn-group">' . $buttons . '</div>';
            })

            ->editColumn('created_at', function ($row) {
                return $row->created_at
                    ? $row->created_at->format('d M Y, h:i A')
                    : '-';
            })

            ->filterColumn('created_at', function ($query, $keyword) {
                $query->whereRaw(
                    "DATE_FORMAT(quiz_attempts.created_at, '%d %b %Y, %h:%i %p') LIKE ?",
                    ["%{$keyword}%"]
                );
            })

            ->rawColumns(['actions']);
    }


    public function query(QuizAttempt $model)
    {
        return $model->with(['student', 'quiz'])->latest('quiz_attempts.created_at');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('quiz-attempts-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1)
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'pageLength' => 10,
            ]);
    }

    protected function getColumns()
    {
        return [
            [
                'data' => 'DT_RowIndex',
                'title' => '#',
                'orderable' => false,
                'searchable' => false,
            ],
            [
                'data' => 'student_name',
                'name' => 'student.name',
                'title' => 'Student',
            ],
            [
                'data' => 'quiz_title',
                'name' => 'quiz.title',
                'title' => 'Quiz',
            ],
            [
                'data' => 'total_questions',
                'title' => 'Total Qs',
            ],
            [
                'data' => 'correct_answers',
                'title' => 'Correct',
            ],
            [
                'data' => 'wrong_answers',
                'title' => 'Wrong',
            ],
            [
                'data' => 'score',
                'title' => 'Score',
            ],
            [
                'data' => 'created_at',
                'title' => 'Attempted At',
            ],
            ['data' => 'actions', 'title' => 'Actions', 'orderable' => false, 'searchable' => false],

        ];
    }

    protected function filename(): string
    {
        return 'QuizAttempts_' . date('YmdHis');
    }
}
