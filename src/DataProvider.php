<?php

//Savvin Ruslan 2024

/**
 * Sample of the database read function call
 * @example
 * $data["queryParams"] = [
 *     "select" => [
 *         "0" => ["action" => "+", "prefix" => "main_table_name", "columns" => "column_name_1,column_name_N AS column_name_NN"],
 *         "1" => ["action" => "-", "prefix" => "related_table_1", "columns" => "column_name_2 AS column_name_22,column_name_N"],
 *         "2" => ["action" => "+", "prefix" => "related_table_2", "columns" => "*"]
 *     ],
 *     "tables" => ["main_table_name", "related_table_1", "related_table_2"],
 *     "join" => "LEFT JOIN related_table_1 ON main_table_name.column_name_1 = related_table_1.column_name_3 
 *                 LEFT JOIN languages ON languages.id = COALESCE({DATA_HERE/languages.id/" . $languageId . "}, bookings.languageId)
 *     "where" => "WHERE main_table_name.column_name_1 IN (" . $values . ")",
 *     "groupBy" => "GROUP BY main_table_name.column_name_1",
 *     "orderBy" => "ORDER BY main_table_name.column_name_5",
 *     "limit" => "LIMIT 10",
 *     "buildMap" => [["column_name_1"], ["column_name_2", "column_name_3", "column_name_4"]]
 * ];
 * // buildMap: Reconstructs the output object by grouping fields.
 * // The first array defines the grouping keys, the second defines the nested data structure.
 * $records = DataProvider::getInstance()->read($data);
 */
/**
 *Delete with condition
 * @example    
 *
 *$where = ["changed" => 4, "where" => "WHERE main_table_name.column_name_1 IN (" . $values . ")"];
 *$data = [
 *   "table" => "main_table_name",
 *   "queryData" => [
 *   "0" => array_merge($insert, $where)
 *   ]
 *     ];
 * DataProvider::getInstance()->save($data);
 */
/**
 * Write to database function (Insert/Update) with condition
 * @example
 * $actionData = ["insert" => ["column_name_2" => $value]];
 * $condition = [
 *     "changed" => 5, 
 *     "where" => "WHERE main_table.column_name_1 IN (" . $values . ")"
 * ];
 * $data = [
 *     "table" => "main_table",
 *     "queryData" => [
 *         "0" => array_merge($actionData, $condition)
 *     ]
 * ];
 * //Execution
 * DataProvider::getInstance()->save($data);
 */
/**
 * Unconditional database insert method.
 * Convenient for bulk inserting a large number of rows.
 * @example
 * $data = [
 *     "table" => "imageResizes",
 *     "queryData" => [
 *         "0" => ["changed" => 1, "column_name_1" => values1,"column_name_N" => valuesN],
 *         "1" => ["changed" => 1, "column_name_2" => values2,"column_name_2N" => values2N]
 *     ]
 * ];
 * DataProvider::getInstance()->save($data);
 */
/**
 * Unconditional database update method.
 * Note: The presence of a primary key ID is mandatory. 
 * Convenient for bulk Updating a large number of rows.
 * @example
 * $data = [
 *     "table" => "imageResizes",
 *     "queryData" => [
 *         "0" => ["id" = 1 ,"changed" => 3, "column_name_1" => values1,"column_name_N" => valuesN],
 *         "1" => ["id" = N ,"changed" => 3, "column_name_2" => values2,"column_name_2N" => values2N]
 *     ]
 * ];
 * DataProvider::getInstance()->save($data);
 */
/**
 * Unconditional database Delete method.
 * Note: The presence of a primary key ID is mandatory. 
 * Convenient for bulk Delete a large number of rows.
 * @example
 * $data = [
 *     "table" => "imageResizes",
 *     "queryData" => [
 *         "0" => ["id" = 1 ,"changed" => 2],
 *         "1" => ["id" = N ,"changed" => 2]
 *     ]
 * ];
 * DataProvider::getInstance()->save($data);
 */

namespace App;

use GuzzleHttp\Psr7\Message;

class DataProvider
{
    private \mysqli $sqlLink;
    public string $environment;
    public array $execResult = [];
    private static ?DataProvider $instance = null;

    private function __construct()
    {
        $sqlLink = new SqlLink();
        $this->sqlLink = $sqlLink->link;
        $this->environment = $sqlLink->environment;
    }

    public static function getInstance(): DataProvider
    {
        return self::$instance ??= new self();
    }

    public function commitTransaction(): void
    {
        $this->sqlLink->commit();
    }

    public function begin()
    {
        $this->sqlLink->begin_transaction();
    }

    public function commit()
    {
        $this->sqlLink->commit();
    }

    public function rollback()
    {
        $this->sqlLink->rollback();
    }

    public function save(array $data)
    {
        $object = $data["queryData"];
        $tableName = $data["table"];
        $extraParameter = $data["extraParameter"] ?? [];
        if (count($object) === 0 or count($object[0]) === 0) {
            ReturnData::getInstance()->set("message.dataProvider.NoDataToSave", ReturnType::MESSAGE, "ℹ️", ReturnDirection::USER);
        }
        $toInsert = [];
        $toUpdate = [];
        $toDelete = [];
        $toDeleteArbitraryQuery = [];
        $isArbitraryQuery = false;
        // $row["changed"] = 1 - last action: insert new record
        // $row["changed"] = 2 - last action: delete record
        // $row["changed"] = 3 - last action: update record
        foreach ($object as $row) {
            $status = isset($row["changed"]) ? (int) $row["changed"] : 0;
            $hasId = isset($row["id"]);
            switch (true) {
                case ($status === 1 && !$hasId):
                    //Insert: if status is 1 AND id key DOES NOT exist
                    $toInsert[] = $row;
                    break;
                case ($status === 3 && $hasId):
                    //Update: if status is 1 or 3 AND id key EXISTS
                    $toUpdate[] = $row;
                    break;
                case ($status === 2 && $hasId):
                    //Delete: if status is 2 AND id key EXISTS
                    $toDelete[] = $row;
                    break;
                case ($status === 4):
                    $toDeleteArbitraryQuery[] = $row;
                    break;
                case ($status === 5):
                    $isArbitraryQuery = true;
                    break;
            }
        }

        if (count($toInsert) !== 0) {
            $this->insert($tableName, $toInsert, $extraParameter);
        }
        if (count($toUpdate) !== 0) {
            $this->update($tableName, $toUpdate);
        }
        if (count($toDelete) !== 0) {
            $this->delete($tableName, $toDelete);
        }
        if (count($toDeleteArbitraryQuery) !== 0) {
            $this->deleteArbitraryQuery($data, $toDeleteArbitraryQuery);
        }
        if ($isArbitraryQuery) {
            $this->addArbitraryQuery($data, $extraParameter);
        }
        if (empty($toInsert) && empty($toUpdate) && empty($toDelete) && empty($toDeleteArbitraryQuery) && $isArbitraryQuery !== true) {
            ReturnData::getInstance()->set("No actions (insert, update, or delete) defined.", ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::LOG);
        }
    }

    private function addArbitraryQuery(array $data, array $extraParameter)
    {
        $table = $data["table"];
        $index = $data["index"];
        $queryData = $data["queryData"];
        $sql = "SELECT COLUMN_NAME 
        FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ? 
          AND INDEX_NAME = ?
        ORDER BY SEQ_IN_INDEX ASC";
        $stmt = $this->sqlLink->prepare($sql);
        $finalTypes = "ss";
        $sqlParams = [$table, $index];
        $stmt->bind_param($finalTypes, ...$sqlParams);
        $stmt->execute();
        $result = $stmt->get_result();
        $indexFields = [];
        while ($row = $result->fetch_assoc()) {
            $indexFields[] = $row['COLUMN_NAME'];
        }
        if (empty($indexFields)) {
            $mes = "Index fields not found or index does not exist.";
            ReturnData::getInstance()->set($mes, ReturnType::MESSAGE, "❗ ", ReturnDirection::LOG);
        }
        $selectColumns = ["id"];
        $whereParts = [];
        foreach ($indexFields as $field) {
            if (!array_key_exists($field, $queryData[0])) {
                $mes = "Missing required index field '{$field}' in the input array.";
                ReturnData::getInstance()->set($mes, ReturnType::MESSAGE, "❗ ", ReturnDirection::LOG);
            }
            $fieldValues = [];
            foreach ($queryData as $row) {
                $fieldValues[] = $this->sqlLink->real_escape_string((string)$row[$field]);
            }
            $uniqueValues = array_unique($fieldValues);
            $escapedValuesString = "'" . implode("', '", $uniqueValues) . "'";
            $whereParts[] = "{$table}.{$field} IN ({$escapedValuesString})";
            $selectColumns[] = $field;
        }
        $where = "WHERE " . implode(' AND ', $whereParts);
        $columnsString = implode(', ', $selectColumns);
        $dataTemp = [];
        $dataTemp["queryParams"] = [
            "select" => [
                0 => ["action" => "+", "prefix" => $table, "columns" => $columnsString]
            ],
            "tables" => [$table],
            "where" => $where,
        ];
        $records = $this->read($dataTemp);
        $dbMap = [];
        // Check if there are existing records from the database
        if (!empty($records)) {
            foreach ($records as $dbRow) {
                $keyParts = [];
                foreach ($indexFields as $field) {
                    $keyParts[] = $dbRow[$field] ?? '';
                }
                // Create a unique composite key based on indexing fields
                $mapKey = implode('_', $keyParts);
                // Map the unique key to the corresponding database record ID
                $dbMap[$mapKey] = $dbRow['id'] ?? null;
            }
        }
        // Iterate through the input data to determine whether to insert or update
        foreach ($queryData as &$item) {
            $itemKeyParts = [];
            foreach ($indexFields as $field) {
                $itemKeyParts[] = $item[$field] ?? '';
            }
            // Generate the same composite key for the input item
            $itemKey = implode('_', $itemKeyParts);

            // If the key exists in the database map, flag it for Update, otherwise flag for Insert
            if (isset($dbMap[$itemKey])) {
                $item['id'] = $dbMap[$itemKey];
                $item['changed'] = 3; // 3 = Update existing record
            } else {
                $item['changed'] = 1; // 1 = Insert new record
            }
        }
        // Unset the reference variable to prevent accidental side effects later in the script
        unset($item);
        $dataTemp = [];
        $dataTemp = [
            "table" => "$table",
            "queryData" => $queryData
        ];
        DataProvider::getInstance()->save($dataTemp);
    }

    private function deleteArbitraryQuery(array $data, array $object)
    {
        $table = $data["table"];
        $typesMap = $this->getTableTypes([$table], true);
        $where = $data["where"];
        $params = isset($object["params"]) ? $object["params"] : [];
        $finalTypes = "";
        $sqlParams = [];
        $where = $this->setPlaceholders($where, $typesMap, $sqlParams, $finalTypes);
        $sql = "DELETE FROM " . $table . " " . $where;
        $stmt = $this->sqlLink->prepare($sql);
        if (!empty($sqlParams)) {
            $stmt->bind_param($finalTypes, ...$sqlParams);
        }
        $stmt->execute();
    }

    private function update(string $table, array $object)
    {
        if (empty($object)) return;
        $tables = [$table];
        $typesMap = $this->getTableTypes($tables, false);
        $allObjectKeys = [];
        foreach ($object as $row) {
            $allObjectKeys = array_merge($allObjectKeys, array_keys($row));
        }
        $allObjectKeys = array_unique($allObjectKeys);
        foreach ($allObjectKeys as $key) {
            if ($key !== 'changed' && !array_key_exists($key, $typesMap)) {
                $mes = "Security Alert: Field '{$key}' does not exist in database table '{$table}'. Add a safeguard on the frontend (JS).";
                ReturnData::getInstance()->set($mes, ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::LOG);
            }
        }
        $columns = array_values(array_intersect($allObjectKeys, array_keys($typesMap)));
        $rowPlaceholders = "ROW(" . implode(",", array_fill(0, count($columns), "?")) . ")";
        $rowCount = count($object);
        $allPlaceholders = implode(", ", array_fill(0, $rowCount, $rowPlaceholders));
        $setParts = [];
        foreach ($columns as $col) {
            if ($col === "id") continue;
            $setParts[] = "target.`$col` = COALESCE(source.`$col`, target.`$col`)";
        }
        $setSql = implode(", ", $setParts);
        $colNamesList = implode(", ", $columns);
        $sql = "UPDATE `$table` AS target
    INNER JOIN (VALUES $allPlaceholders) AS source ($colNamesList)
    ON target.id = source.id
    SET $setSql";
        $finalValues = [];
        $finalTypes = "";
        foreach ($object as $row) {
            foreach ($columns as $col) {
                $val = $row[$col] ?? null;
                $finalValues[] = $val;
                $finalTypes .= $typesMap[$col] ?? "s";
            }
        }
        $stmt = $this->sqlLink->prepare($sql);
        $stmt->bind_param($finalTypes, ...$finalValues);
        $stmt->execute();
    }

    private function insert(string $table, array $object, array $extraParameter)
    {
        $tables = [$table];
        $typesMap = $this->getTableTypes($tables, false);
        $allObjectKeys = array_keys($object[0]);
        foreach ($allObjectKeys as $key) {
            if ($key !== 'changed' && !array_key_exists($key, $typesMap)) {
                $mes = "Security Alert: Field '{$key}' does not exist in database table '{$table}'. Add a safeguard on the frontend (JS).";
                ReturnData::getInstance()->set($mes, ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::LOG);
            }
        }
        $columns = array_intersect($allObjectKeys, array_keys($typesMap));
        $rowPlaceholders = "ROW(" . implode(",", array_fill(0, count($columns), "?")) . ")";
        $rowCount = count($object);
        $allPlaceholders = implode(", ", array_fill(0, $rowCount, $rowPlaceholders));
        $setParts = [];
        foreach ($columns as $col) {
            if ($col === "id")
                continue;
            $setParts[] = "target.`$col` = source.`$col`";
        }
        $setSql = implode(", ", $setParts);
        $colNamesList = implode(", ", $columns);
        $ignore = "";
        if (($extraParameter["ignore"] ?? false) === true) {
            $ignore = "IGNORE";
        }
        $sql = "INSERT " . $ignore .  " INTO `$table` ($colNamesList) VALUES $allPlaceholders";
        $finalValues = [];
        $finalTypes = "";
        foreach ($object as $row) {
            foreach ($columns as $col) {
                $val = $row[$col];
                $finalValues[] = $val;
                $finalTypes .= $typesMap[$col] ?? "s";
            }
        }
        $stmt = $this->sqlLink->prepare($sql);
        if (!empty($finalValues)) {
            $stmt->bind_param($finalTypes, ...$finalValues);
        }
        $stmt->execute();
        $firstId = $stmt->insert_id;
        $affectedRows = $stmt->affected_rows;
        $allIds = $affectedRows > 0 ? range($firstId, $firstId + $affectedRows - 1) : [];
        $this->execResult = ["insertId" => $allIds, "affectedRows" => $affectedRows];
    }

    private function delete(string $tableName, array $object)
    {
        $ids = array_column($object, "id");
        $placeholders = implode(",", array_fill(0, count($ids), "?"));
        $types = str_repeat("i", count($ids));
        $sql = "DELETE FROM `$tableName` WHERE `id` IN ($placeholders)";
        $stmt = $this->sqlLink->prepare($sql);
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
    }

    public function read(array $data)
    {
        $tableNames = $data["queryParams"]["tables"];
        $tableName = $data["queryParams"]["tables"][0];
        $typesMap = $this->getTableTypes($tableNames, true);
        $arrSelect = $data["queryParams"]["select"];
        $select = "SELECT ";
        $parts = [];
        foreach ($arrSelect as $row) {
            $columns = $row["columns"];
            $action = $row["action"] ?? '';
            $prefix = !empty($row["prefix"]) ? $row["prefix"] : '';
            if (!empty($action) && $action === "-") {
                $toRemove = array_map('trim', explode(",", $columns));
                if (!empty($prefix)) {
                    foreach ($toRemove as $key => $col) {
                        $toRemove[$key] =  $prefix . "." . $col;
                    }
                }
                $parts = array_diff($parts, $toRemove);
            } else if ($columns === "*") {
                if (!empty($prefix)) {
                    $searchPrefix = $prefix . ".";
                    foreach (array_keys($typesMap) as $fullColumnName) {
                        if (strpos($fullColumnName, $searchPrefix) === 0) {
                            $parts[] = $fullColumnName;
                        }
                    }
                } else {
                    $parts = array_merge($parts, array_keys($typesMap));
                }
            } else {
                if (!empty($prefix)) {
                    $prefixDot = $prefix . ".";
                    $colsArray = explode(",", $columns);
                    foreach ($colsArray as $col) {
                        $trimmedCol = trim($col);
                        if (strpos($trimmedCol, '(') !== false || strpos($trimmedCol, '.') !== false) {
                            $parts[] = $trimmedCol;
                        } else {
                            $parts[] = $prefixDot . $trimmedCol;
                        }
                    }
                } else {
                    $parts[] = $columns;
                }
            }
        }
        $select .= implode(", ", array_unique(array_filter($parts)));
        $where = $data["queryParams"]["where"] ?? "";
        $joins = $data["queryParams"]["join"] ?? "";
        $groupBy = $data["queryParams"]["groupBy"] ?? "";
        $orderBy = $data["queryParams"]["orderBy"] ?? "";
        $limit = $data["queryParams"]["limit"] ?? "";
        $finalTypes = "";
        $sqlParams = [];
        $sql = $select . " FROM " . $tableName . " " . $joins . " " . $where . " " . $groupBy . " " . $orderBy;
        $sql = $this->setPlaceholders($sql, $typesMap, $sqlParams, $finalTypes);
        $sql = $this->setLimit($sql, $limit, $sqlParams, $finalTypes);
        try {
            $stmt = $this->sqlLink->prepare($sql);
        } catch (\Throwable $e) {
            $mes = $e->getMessage() . " " . $e->getFile() . " " . $e->getLine() . " " . $sql;
            ReturnData::getInstance()->set($mes, ReturnType::THROW_EXCEPTION, "", ReturnDirection::LOG);
        }
        if (!empty($sqlParams)) {
            $stmt->bind_param($finalTypes, ...$sqlParams);
        }
        $stmt->execute();
        $previousResult = $stmt->get_result();
        $result = $previousResult->fetch_all(MYSQLI_ASSOC);
        if (isset($data["queryParams"]["buildMap"]) && $data["queryParams"]["buildMap"] !== "" && $data["queryParams"]["buildMap"] !== []) {
            return $this->buildMap($result, $data["queryParams"]["buildMap"]);
        } else {
            return $result;
        }
    }

    private function setPlaceholders(string $sql, array $typesMap, array &$p, string &$t): string
    {
        if (empty($sql)) return $sql;
        $pattern = '/\{DATA_HERE\/([\w.->\'$]+)\/(.+?)\}|([\w.->\'$]+)\s+(IN|NOT IN)\s*\(([^)]+)\)/is';
        return preg_replace_callback($pattern, function ($m) use (&$p, &$t, $typesMap) {
            $isTemplate = !empty($m[1]);
            $column = $isTemplate ? $m[1] : $m[3];
            $valString = $isTemplate ? $m[2] : $m[5];
            $operator = $isTemplate ? '' : strtoupper($m[4]);

            $char = $typesMap[$column] ?? 's';
            $placeholders = [];
            foreach (explode(',', $valString) as $v) {
                $v = trim($v, " '\"");
                if (strtoupper($v) === 'NULL' || $v === '') {
                    $p[] = null;
                    $t .= 's';
                } else {
                    $p[] = ($char === 'i') ? (int)$v : $v;
                    $t .= $char;
                }
                $placeholders[] = "?";
            }
            $res = implode(',', $placeholders);
            return $isTemplate ? $res : "$column $operator ($res)";
        }, $sql);
    }

    private function setLimit(string $sql, string $limit, array &$p, string &$t): string
    {
        if ($limit !== null && $limit !== "") {
            $val = (int)str_ireplace('LIMIT', '', $limit);
            $p[] = ($val <= 0) ? 99999 : $val;
            $t .= "i";
            $sql .= " LIMIT ?";
        }
        return $sql;
    }

    private function buildMap(array $data, array $mapConfig): array
    {
        $result = [];
        $pathKeys = $mapConfig[0] ?? [];
        $valueKeys = $mapConfig[1] ?? [];
        foreach ($data as $row) {
            $current = &$result;
            foreach ($pathKeys as $keyName) {
                $val = $row[$keyName] ?? "unknown";
                if (!isset($current[$val])) {
                    $current[$val] = [];
                }
                $current = &$current[$val];
            }
            if (count($valueKeys) === 1) {
                $vKey = $valueKeys[0];
                $val = $row[$vKey] ?? null;
                $current = [$vKey => $val];
            } else {
                foreach ($valueKeys as $vKey) {
                    $current[$vKey] = $row[$vKey] ?? null;
                }
            }
        }
        return $result;
    }

    private function getTableTypes(array $tableNames, string $addNameTable)
    {
        $typesMap = [];
        foreach ($tableNames as $table) {
            $result = $this->sqlLink->query("DESCRIBE `$table` ");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $type = strtolower($row["Type"]);
                    $char = (strpos($type, "int") !== false) ? "i" : (preg_match("/(decimal|float|double)/", $type) ? "d" : "s");
                    if ($addNameTable) {
                        $typesMap["$table." . $row["Field"]] = $char;
                    } else {
                        $typesMap[$row["Field"]] = $char;
                    }
                }
            }
        }
        return $typesMap;
    }

    public function findRowByJsonPair(array $data)
    {
        $value = [$data["value"]];
        $sql = $data["sql"];
        $stmt = $this->sqlLink->prepare($sql);
        $stmt->bind_param("s", ...$value);
        $stmt->execute();
        $previousResult = $stmt->get_result();
        return $previousResult->fetch_all(MYSQLI_ASSOC);
    }
}
