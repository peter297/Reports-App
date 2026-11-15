<?php

namespace Tests\Feature;

use App\Support\SyllabusStructure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Tests\TestCase;

class SyllabusImportTest extends TestCase
{
    use RefreshDatabase;

    private function makeFile(string $relativeName, string $contents): string
    {
        $path = storage_path('app/testing/'.$relativeName);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $contents);

        return $path;
    }

    public function test_imports_simple_csv_layout(): void
    {
        $path = $this->makeFile('simple.csv', "Numbers,Whole Numbers,Counting in ones\nNumbers,Whole Numbers,Counting in twos\nNumbers,Fractions,Introduction to fractions\nMeasurement,Length,Units of measurement\n");

        $result = app(SyllabusStructure::class)->stateFromFile($path, []);

        $this->assertSame(4, $result['imported_lessons']);
        $this->assertSame(2, $result['strands']);
        $this->assertSame(3, $result['substrands']);
        $this->assertNull($result['declared_out_of']);

        $strands = array_values($result['state']);
        $numbers = collect($strands)->firstWhere('name', 'Numbers');
        $this->assertNotNull($numbers);
        $this->assertCount(2, $numbers['substrands'][array_key_first($numbers['substrands'])]['lessons'] ?? []);
    }

    public function test_imports_school_export_layout_with_sparse_cells(): void
    {
        $csv = implode("\n", [
            ',LESSON NO,Out of,STRAND,SUB-STRAND,LESSON TOPICS ,,,,,,',
            ',1,173,NUMBERS,PRE-NUMBER ACTIVITIES (20 LESSONS).,Sorting objects by colour.,,,,,,',
            ',2,173,,,Sorting objects by use.,,,,,,',
            ',3,173,,,Sorting objects by shape.,,,,,,',
            ',21,173,,WHOLE NUMBERS ( 25 lessons),Place value,,,,,,',
            ',200,173,MEASUREMENT.,LENGTH (10 lessons),Units of length,,,,,,',
            ',201,173,,,Comparing lengths,,,,,,',
        ]);

        $path = $this->makeFile('school.csv', $csv);

        $result = app(SyllabusStructure::class)->stateFromFile($path, []);

        $this->assertSame(6, $result['imported_lessons']);
        $this->assertSame(2, $result['strands']);
        $this->assertSame(173, $result['declared_out_of']);

        $strands = array_values($result['state']);
        $numbers = collect($strands)->firstWhere('name', 'NUMBERS');
        $this->assertNotNull($numbers);
        $substrands = array_values($numbers['substrands']);
        $this->assertSame('PRE-NUMBER ACTIVITIES (20 LESSONS).', $substrands[0]['name']);
        $this->assertCount(3, $substrands[0]['lessons']);
        $this->assertSame('Place value', $substrands[1]['lessons'][0]['topic']);

        $measurement = collect($strands)->firstWhere('name', 'MEASUREMENT.');
        $this->assertNotNull($measurement);
        $this->assertSame('LENGTH (10 lessons)', array_values($measurement['substrands'])[0]['name']);
    }

    public function test_imports_xlsx_school_export_layout(): void
    {
        $path = storage_path('app/testing/school.xlsx');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $writer = new XlsxWriter;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(['', 'LESSON NO', 'Out of', 'STRAND', 'SUB-STRAND', 'LESSON TOPICS ', '', '']));
        $writer->addRow(Row::fromValues(['', 1, 173, 'NUMBERS', 'PRE-NUMBER ACTIVITIES', 'Sorting objects by colour.', '', '']));
        $writer->addRow(Row::fromValues(['', 2, 173, '', '', 'Sorting objects by use.', '', '']));
        $writer->addRow(Row::fromValues(['', 3, 173, 'MEASUREMENT.', 'LENGTH (10 lessons)', 'Units of length', '', '']));
        $writer->close();

        $result = app(SyllabusStructure::class)->stateFromFile($path, []);

        $this->assertSame(3, $result['imported_lessons']);
        $this->assertSame(2, $result['strands']);
        $this->assertSame(173, $result['declared_out_of']);
    }

    public function test_simple_csv_with_header_row(): void
    {
        $path = $this->makeFile('header.csv', "strand,substrand,topic\nNumbers,Whole Numbers,Counting\nMeasurement,Length,Units\n");

        $result = app(SyllabusStructure::class)->stateFromFile($path, []);

        $this->assertSame(2, $result['imported_lessons']);
        $this->assertSame(2, $result['strands']);
    }

    public function test_file_with_no_lesson_rows_returns_zero_counts(): void
    {
        $path = $this->makeFile('empty.csv', ",,\nheader row,,,,\n");

        $result = app(SyllabusStructure::class)->stateFromFile($path, []);

        $this->assertSame(0, $result['imported_lessons']);
        $this->assertSame([], $result['state']);
    }

    public function test_import_merges_with_existing_state(): void
    {
        $path = $this->makeFile('merge.csv', "Numbers,Whole Numbers,Counting\n");

        $current = [
            'key-1' => [
                'id' => null,
                'name' => 'Measurement',
                'description' => null,
                'expected_completion_date' => null,
                'substrands' => [
                    'key-2' => ['id' => null, 'name' => 'Length', 'description' => null, 'lessons' => [['id' => null, 'topic' => 'Metre']]],
                ],
            ],
        ];

        $result = app(SyllabusStructure::class)->stateFromFile($path, $current);

        $this->assertSame(1, $result['imported_lessons']);
        $this->assertCount(2, $result['state']);
    }

    protected function tearDown(): void
    {
        $dir = storage_path('app/testing');

        if (is_dir($dir)) {
            foreach (glob($dir.'/*') as $file) {
                @unlink($file);
            }
            @rmdir($dir);
        }

        parent::tearDown();
    }
}
