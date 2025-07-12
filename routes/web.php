<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/', function() {
	return view('app');
});

Route::get('/dl/cards', function() {
	set_time_limit(0);
	$path = storage_path('app/public/images/cards');
	$zip_path = storage_path('app/private');
	if (!file_exists($zip_path)) {
		mkdir($zip_path, 0755, true);
	}

	$zip_file = $zip_path . '/cards.zip';

	$zip = new \ZipArchive;
	$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
	$files = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator($path),
		\RecursiveIteratorIterator::LEAVES_ONLY
	);

	$count = 0;
	foreach ($files as $file) {
		if ($file->isDir()) {
			continue;
		}

		$file_path = $file->getRealPath();
		if (Cache::has('carddlfile:' . $file_path)) {
			continue;
		}

		$relative_path = substr($file_path, strlen($path) + 1);
		$zip->addFile($file_path, $relative_path);
		Cache::put('carddlfile:' . $file_path, true, now()->addHour());
		if (++$count === 300) {
			break;
		}
	}

	$zip->close();

	return response()->download($zip_file, 'cards.zip', ['Content-Type' => 'application/octet-stream'])->deleteFileAfterSend(true);
});

Route::get('/verify/email/{token}', function($token) {
	return view('app');
})->name('email.verify.token');

Route::get('/forgot/password/{token}', function($token) {
	return view('app');
})->name('forgot.password.token');

Route::fallback(function() {
	return view('app');
});