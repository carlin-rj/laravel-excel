<?php

namespace Mckue\Excel\Imports;

use Vtiful\Kernel\Excel;
use Mckue\Excel\Concerns\SkipsEmptyRows;
use Mckue\Excel\Concerns\ToModel;
use Mckue\Excel\Concerns\WithBatchInserts;
use Mckue\Excel\Concerns\WithColumnLimit;
use Mckue\Excel\Concerns\WithMapping;
use Mckue\Excel\Concerns\WithValidation;
use Mckue\Excel\Helpers\ArrayHelper;
use Mckue\Excel\Validators\ValidationException;

class ModelImporter
{
    private ModelManager $manager;

    public function __construct(ModelManager $manager)
    {
        $this->manager = $manager;
    }

	/**
	 * @param Excel $excel
	 * @param ToModel $import
	 * @param int $startRow
	 * @throws ValidationException
	 */
    public function import(Excel $excel, ToModel $import, int $startRow = 1): void
    {
        $batchSize        = $import instanceof WithBatchInserts ? $import->batchSize() : 1;
        $withMapping      = $import instanceof WithMapping;
        $withValidation   = $import instanceof WithValidation && method_exists($import, 'prepareForValidation');
        $endColumn        = $import instanceof WithColumnLimit ? $import->endColumn() : null;

        $this->manager->setRemembersRowNumber(method_exists($import, 'rememberRowNumber'));

		$worksheet = $excel->setSkipRows($startRow);
		$endColumnIndex = $endColumn ? Excel::columnIndexFromString($endColumn) : null;

		$i = $startRow - 1;
		while (($rowArray = $worksheet->nextRow()) !== NULL) {
			$i++;

			if ($endColumnIndex !== null) {
				$rowArray = array_slice((array)$rowArray, 0, $endColumnIndex + 1);
			}

			//如果是空数组则直接跳过
			if ($import instanceof SkipsEmptyRows && ArrayHelper::isEmpty($rowArray)) {
				continue;
			}

			if ($withValidation) {
				$rowArray = $import->prepareForValidation($rowArray, $i);
			}

			if ($withMapping) {
				$rowArray = $import->map($rowArray);
			}

			$this->manager->add(
				$i,
				$rowArray
			);

			// Flush each batch.
			if (($i % $batchSize) === 0) {
				$this->manager->flush($import, $batchSize > 1);
				$i = 0;
			}
		}

		$this->manager->flush($import, $batchSize > 1);
    }
}
