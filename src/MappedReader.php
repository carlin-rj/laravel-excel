<?php

namespace Mckue\Excel;

use Illuminate\Support\Collection;
use Vtiful\Kernel\Excel;
use Mckue\Excel\Concerns\ToArray;
use Mckue\Excel\Concerns\ToCollection;
use Mckue\Excel\Concerns\ToModel;
use Mckue\Excel\Concerns\WithMappedCells;

class MappedReader
{
	/**
	 * @param WithMappedCells $import
	 * @param Excel $worksheet
	 * @throws \Throwable
	 */
    public function map(WithMappedCells $import, Excel $worksheet)
    {
		$mapped = [];
		$mapping = $import->mapping();

		$rowIndex = 0;
		while ($row = $worksheet->nextRow()) {
			foreach ($mapping as $name => $coordinate) {
				[$column, $targetRow] = $this->coordinateToPosition($coordinate); // Convert A1-like coordinate to numeric form

				if ($rowIndex === $targetRow && isset($row[$column])) {
					$mapped[$name] = $row[$column];
				}
			}
			$rowIndex++;
		}

		if ($import instanceof ToModel) {
			$model = $import->model($mapped);

			if ($model) {
				$model->saveOrFail();
			}
		}

		if ($import instanceof ToCollection) {
			$import->collection(new Collection($mapped));
		}

		if ($import instanceof ToArray) {
			$import->array($mapped);
		}
    }

	private function coordinateToPosition($coordinate): array
	{
		$column = Excel::columnIndexFromString(substr($coordinate, 0, 1)) - 1;  // Convert column letter to index (0-based)
		$row = (int)substr($coordinate, 1) - 1;  // Rows in xlswriter are 0-indexed

		return [$column, $row];
	}
}
