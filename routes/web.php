<?php

use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportTemplateController;
use Illuminate\Support\Facades\Route;

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

Route::get('/events/pdf', [EventController::class, 'generatePDF'])->name('events.pdf')->middleware('auth');
Route::get('/events/uploaded-calendar', [EventController::class, 'downloadUploadedCalendar'])->name('events.calendar.download')->middleware('auth');
Route::get('/events/calendar/{year}/{month}', [EventController::class, 'calendarPdf'])->name('events.calendar.pdf')->middleware('auth');
Route::get('/events/{event}/calendar', [EventController::class, 'previewCalendar'])->name('events.calendar')->middleware('auth');
Route::get('/reports/{report}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf')->middleware('auth');
Route::get('/enrollments/pdf', [EnrollmentController::class, 'pdf'])->name('enrollments.pdf')->middleware('auth');
Route::get('/enrollments/excel', [EnrollmentController::class, 'excel'])->name('enrollments.excel')->middleware('auth');
Route::get('/attendances/{period}/pdf', [AttendanceReportController::class, 'pdf'])->name('attendances.report.pdf')->middleware('auth');
Route::get('/attendances/{period}/excel', [AttendanceReportController::class, 'excel'])->name('attendances.report.excel')->middleware('auth');
Route::get('/attendances/range/pdf', [AttendanceReportController::class, 'pdfByDate'])->name('attendances.report.range.pdf')->middleware('auth');
Route::get('/attendances/range/excel', [AttendanceReportController::class, 'excelByDate'])->name('attendances.report.range.excel')->middleware('auth');
Route::get('/attendances/selected/pdf', [AttendanceReportController::class, 'pdfByIds'])->name('attendances.report.selected.pdf')->middleware('auth');
Route::get('/attendances/selected/excel', [AttendanceReportController::class, 'excelByIds'])->name('attendances.report.selected.excel')->middleware('auth');
Route::get('/report-templates/{reportTemplate}/download', [ReportTemplateController::class, 'download'])->name('report-templates.download')->middleware('auth');
