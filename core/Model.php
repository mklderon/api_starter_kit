<?php

namespace Core;

/**
 * Class Model
 * 
 * Abstract base class for database models.
 * Provides methods for common database operations (CRUD).
 */
abstract class Model
{
    /** @var Database|null The database instance */
    protected $db;

    /** @var string The database table name */
    protected $table;

    /** @var string The primary key column */
    protected $primaryKey = 'id';

    /** @var array Fillable fields for mass assignment */
    protected $fillable = [];

    /** @var array Fields to exclude from query results */
    protected $invisible = ['created_at', 'updated_at'];

    /** @var bool Whether to include timestamps */
    protected $timestamps = true;

    /** @var Logger Logger instance for logging model events */
    protected $logger;

    /**
     * Model constructor.
     *
     * @param Database|null $db Database instance
     * @throws \Exception If database connection is null
     */
    public function __construct(?Database $db)
    {
        if ($db === null) {
            throw new \Exception("Database connection is null (DB_ENABLE may be false)");
        }
        $this->db = $db;
        $this->logger = new Logger();
    }

    /**
     * Check if database operations are enabled.
     *
     * @throws \Exception If database is disabled
     */
    protected function checkDatabase(): void
    {
        if ($this->db === null || !config('app.db_enable', false)) {
            throw new \Exception("Database operations are disabled (DB_ENABLE=false)");
        }
    }

    /**
     * Get the table name.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get selectable columns, excluding invisible fields.
     *
     * @param string $columns Columns to select (default: *)
     * @return string
     */
    protected function getSelectableColumns(string $columns = '*'): string
    {
        if ($columns === '*') {
            return implode(', ', array_diff(
                array_merge($this->fillable, [$this->primaryKey]),
                $this->invisible
            ));
        }
        return $columns;
    }

    /**
     * Retrieve all records from the table.
     *
     * @param string $columns Columns to select
     * @param array $filters Where conditions
     * @param int|null $limit Maximum number of records
     * @return array
     * @throws \Exception If query fails
     */
    public function all(string $columns = '*', array $filters = [], ?int $limit = null): array
    {
        try {
            $columns = $this->getSelectableColumns($columns);
            $query = "SELECT {$columns} FROM {$this->table}";
            $params = [];

            if (!empty($filters)) {
                $conditions = [];
                foreach ($filters as $key => $value) {
                    $conditions[] = "{$key} LIKE ?";
                    $params[] = "%{$value}%";
                }
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            if ($limit !== null) {
                $query .= " LIMIT ?";
                $params[] = (int)$limit;
            }

            $result = $this->db->query($query, $params)->fetchAll();
            $this->logger->info("Retrieved records from {$this->table}", ['count' => count($result)]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to retrieve records: " . $e->getMessage(), [
                'table' => $this->table,
                'columns' => $columns,
                'filters' => $filters,
                'limit' => $limit
            ]);
            throw $e;
        }
    }

    /**
     * Find a record by ID.
     *
     * @param mixed $id Record ID
     * @param string $columns Columns to select
     * @return array|null
     * @throws \Exception If query fails
     */
    public function find($id, string $columns = '*'): ?array
    {
        try {
            $columns = $this->getSelectableColumns($columns);
            $query = "SELECT {$columns} FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $result = $this->db->query($query, [$id])->fetch();
            
            $this->logger->info("Found record in {$this->table}", ['id' => $id]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to find record: " . $e->getMessage(), [
                'table' => $this->table,
                'id' => $id,
                'columns' => $columns
            ]);
            throw $e;
        }
    }

    /**
     * Create a new record.
     *
     * @param array $data Data to insert
     * @return bool
     * @throws \Exception If insertion fails
     */
    public function create(array $data): bool
    {
        try {
            $data = $this->filterFillable($data);
            
            if ($this->timestamps) {
                $data = $this->addTimestamps($data);
            }
            
            $result = $this->db->insert($this->table, $data);
            $this->logger->info("Created record in {$this->table}", ['data' => $data]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to create record: " . $e->getMessage(), [
                'table' => $this->table,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update a record by ID.
     *
     * @param mixed $id Record ID
     * @param array $data Data to update
     * @return bool
     * @throws \Exception If update fails or no valid fields provided
     */
    public function update($id, array $data): bool
    {
        try {
            $data = $this->filterFillable($data);
            
            if (empty($data)) {
                throw new \Exception('No valid fields provided for update');
            }
            
            if ($this->timestamps) {
                $data['updated_at'] = now();
            }
            
            $result = $this->db->update($this->table, $data, "{$this->primaryKey} = {$id}");
            $this->logger->info("Updated record in {$this->table}", ['id' => $id, 'data' => $data]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to update record: " . $e->getMessage(), [
                'table' => $this->table,
                'id' => $id,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Delete a record by ID.
     *
     * @param mixed $id Record ID
     * @return \PDOStatement
     * @throws \Exception If deletion fails
     */
    public function delete($id): \PDOStatement
    {
        try {
            $result = $this->db->delete($this->table, "{$this->primaryKey} = ?", [$id]);
            $this->logger->info("Deleted record from {$this->table}", ['id' => $id]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to delete record: " . $e->getMessage(), [
                'table' => $this->table,
                'id' => $id
            ]);
            throw $e;
        }
    }

    /**
     * Filter data to include only fillable fields.
     *
     * @param array $data Input data
     * @return array Filtered data
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Add created_at and updated_at timestamps to data.
     *
     * @param array $data Input data
     * @return array Data with timestamps
     */
    protected function addTimestamps(array $data): array
    {
        $now = now();
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        return $data;
    }

    /**
     * Retrieve all records including timestamps.
     *
     * @param string $columns Columns to select
     * @param array $filters Where conditions
     * @param int|null $limit Maximum number of records
     * @return array
     */
    public function allWithTimestamps(string $columns = '*', array $filters = [], ?int $limit = null): array
    {
        $originalInvisible = $this->invisible;
        $this->invisible = array_diff($this->invisible, ['created_at', 'updated_at']);
        
        $result = $this->all($columns, $filters, $limit);
        
        $this->invisible = $originalInvisible;
        return $result;
    }

    /**
     * Find a record by ID including timestamps.
     *
     * @param mixed $id Record ID
     * @param string $columns Columns to select
     * @return array|null
     */
    public function findWithTimestamps($id, string $columns = '*'): ?array
    {
        $originalInvisible = $this->invisible;
        $this->invisible = array_diff($this->invisible, ['created_at', 'updated_at']);
        
        $result = $this->find($id, $columns);
        
        $this->invisible = $originalInvisible;
        return $result;
    }

    /**
     * Disable timestamps for this model.
     *
     * @return $this
     */
    public function withoutTimestamps(): self
    {
        $this->timestamps = false;
        return $this;
    }

    /**
     * Enable timestamps for this model.
     *
     * @return $this
     */
    public function withTimestamps(): self
    {
        $this->timestamps = true;
        return $this;
    }
}