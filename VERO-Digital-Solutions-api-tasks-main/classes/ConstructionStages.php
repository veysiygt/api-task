<?php
require_once 'Validator.php';

class ConstructionStages
{
    private $db;

    public function __construct()
    {
        $this->db = Api::getDb();
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("
			SELECT
				ID as id,
				name, 
				strftime('%Y-%m-%dT%H:%M:%SZ', start_date) as startDate,
				strftime('%Y-%m-%dT%H:%M:%SZ', end_date) as endDate,
				duration,
				durationUnit,
				color,
				externalId,
				status
			FROM construction_stages
		");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSingle($id)
    {
        $stmt = $this->db->prepare("
			SELECT
				ID as id,
				name, 
				strftime('%Y-%m-%dT%H:%M:%SZ', start_date) as startDate,
				strftime('%Y-%m-%dT%H:%M:%SZ', end_date) as endDate,
				duration,
				durationUnit,
				color,
				externalId,
				status
			FROM construction_stages
			WHERE ID = :id
		");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new construction stage.
     *
     * @param ConstructionStagesCreate $data Data for the construction stage
     *
     * @return mixed The created construction stage or an error message
     */
    public function post(ConstructionStagesCreate $data)
    {
        try {
            Validator::validateName($data->name);
            Validator::validateStartDate($data->startDate);
            Validator::validateEndDate($data->endDate, $data->startDate);
            Validator::validateDurationUnit($data->durationUnit = $data->durationUnit ?: 'DAYS');//Default value 'DAYS'
            Validator::validateColor($data->color = $data->color ?: null);//Default value null
            Validator::validateExternalId($data->externalId = $data->externalId ?: null);//Default value null
            Validator::validateStatus($data->status = $data->status ?: 'NEW');//Default value 'NEW'

            if (isset($data->startDate, $data->endDate)) {
                $startDate = $data->startDate;
                $endDate = $data->endDate;
                $durationUnit = $data->durationUnit;

                // Calculate duration based on start_date, end_date, and durationUnit
                $duration = self::calculateDuration($startDate, $endDate, $durationUnit);

                // Set the calculated duration
                $data->duration = $duration;
            } elseif (!isset($data->endDate)) {
                // If endDate is not provided, set duration and endDate to null
                $data->duration = null;
                $data->endDate = null;
            }

            // If the operation is successful, perform the database insertion
            $stmt = $this->db->prepare("
            INSERT INTO construction_stages
                (name, start_date, end_date, duration, durationUnit, color, externalId, status)
                VALUES (:name, :start_date, :end_date, :duration, :durationUnit, :color, :externalId, :status)
        ");
            $stmt->execute([
                'name' => $data->name,
                'start_date' => $data->startDate,
                'end_date' => $data->endDate,
                'duration' => $data->duration,
                'durationUnit' => $data->durationUnit,
                'color' => $data->color,
                'externalId' => $data->externalId,
                'status' => $data->status,
            ]);

            return $this->getSingle($this->db->lastInsertId());
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Calculates the duration between two dates based on the specified duration unit.
     *
     * @param string $startDate     Start date
     * @param string $endDate       End date
     * @param string $durationUnit  Duration unit (HOURS, DAYS, WEEKS, etc.)
     *
     * @return float|null The calculated duration or null if endDate is empty
     */
    public static function calculateDuration($startDate, $endDate, $durationUnit)
    {
        if (empty($endDate)) {
            return null; // Return null for empty endDate
        }
        $startDateTime = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);
        $interval = $endDateTime->diff($startDateTime);

        switch ($durationUnit) {
            case 'HOURS':
                $duration = ceil($interval->h + ($interval->days * 24)); // Calculate hours as days
                break;
            case 'DAYS':
                $duration = $interval->days;
                break;
            case 'WEEKS':
                $duration = $interval->days / 7;
                break;
            default:
                $duration = $interval->days;
                break;
        }

        return $duration;
    }

    /**
     * Update a construction stage by ID.
     * I defined a separate helper method for the update process and I did the update process through that method. 
     * This method updates the specified construction stage with the provided data.
     *
     * @param int                    $id   The ID of the construction stage to update.
     * @param ConstructionStagesCreate $data The data to update the construction stage with.
     *
     * @return array Returns an array with a success message if the construction stage is updated successfully.
     *               If the construction stage is not found, an array with an error message is returned.
     *               If there is an invalid field or status value, an array with an error message is returned.
     */
    public function patch($id, ConstructionStagesCreate $data)
    {
        $existingData = $this->getSingle($id);
        if (empty($existingData)) {
            return ['error' => 'Construction stage not found.'];
        }


        $fieldsToUpdate = array_filter(get_object_vars($data));
        $validFields = ['name', 'startDate', 'endDate', 'duration', 'durationUnit', 'color', 'externalId', 'status'];

        foreach ($fieldsToUpdate as $field => $value) {
            if (!in_array($field, $validFields)) {
                return ['error' => 'Invalid field: ' . $field];
            }

            // Update only the fields sent by the user
            $existingData[0][$field] = $value;
        }

        // Validate the 'status' field
        if (isset($fieldsToUpdate['status'])) {
            $validStatuses = ['NEW', 'PLANNED', 'DELETED'];
            if (!in_array($fieldsToUpdate['status'], $validStatuses)) {
                return ['error' => 'Invalid status value. Valid values are: ' . implode(', ', $validStatuses)];
            }
        }

        // Update the construction stage in the database
        $this->update($id, $existingData[0]);

        return ['success' => 'Construction stage updated successfully.'];
    }

    /**
     * Delete a construction stage by ID.
     *
     * @param int $id The ID of the construction stage
     * @return array Response array
     */
    public function delete($id)
    {
        $existingData = $this->getSingle($id);
        if (empty($existingData)) {
            return ['error' => 'Construction stage not found.'];
        }

        // Validate the status field
        $validStatuses = ['PLANNED', 'NEW', 'DELETED'];
        if (isset($existingData[0]['status'])) {
            $status = $existingData[0]['status'];
            if (empty($status)) {
                return ['error' => 'Status field is required.'];
            } elseif (!in_array($status, $validStatuses)) {
                return ['error' => 'Invalid status value. Valid values are: ' . implode(', ', $validStatuses)];
            }
        }

        // Change the status to 'DELETED'
        $existingData[0]['status'] = 'DELETED';

        // Update the construction stage in the database
        $this->update($id, $existingData[0]);

        return ['success' => 'Construction stage deleted successfully.'];
    }

    // Helper method to update the construction stage in the database
    private function update($id, $data)
    {
        $stmt = $this->db->prepare("
        UPDATE construction_stages
        SET name = :name,
            status = :status
        WHERE ID = :id
        ");
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'status' => $data['status'],
        ]);
    }
}
