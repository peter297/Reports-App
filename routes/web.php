<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\AttendanceReportController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::redirect('/', '/admin');

// Route::get('/storage-link', function () {
//     $target = '/home/alameena/public_html/reports.alameenacademy.com/storage/app/public/attachments';
//     $shortcut = '/home/alameena/public_html/reports.alameenacademy.com/public/storage/attachments';
//     symlink($target, $shortcut);
//     return 'Symlink created successfully.';
// });



Route::get('/events/pdf', [EventController::class, 'generatePDF'])->name('events.pdf');
Route::get('/reports/{report}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf')->middleware('auth');
Route::get('/enrollments/pdf', [EnrollmentController::class, 'pdf'])->name('enrollments.pdf')->middleware('auth');
Route::get('/enrollments/excel', [EnrollmentController::class, 'excel'])->name('enrollments.excel')->middleware('auth');
Route::get('/attendances/{period}/pdf', [AttendanceReportController::class, 'pdf'])->name('attendances.report.pdf')->middleware('auth');
Route::get('/attendances/{period}/excel', [AttendanceReportController::class, 'excel'])->name('attendances.report.excel')->middleware('auth');
