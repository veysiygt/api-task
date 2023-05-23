<?php

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

	public function post(ConstructionStagesCreate $data)
	{
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
        
        // Validate the status and name fields
        if (isset($data->status, $data->name) && (empty($data->status) || empty($data->name))) {
            return ['error' => 'Status and Name fields are required.'];
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
                start_date = :start_date,
                end_date = :end_date,
                duration = :duration,
                durationUnit = :durationUnit,
                color = :color,
                externalId = :externalId,
                status = :status
            WHERE ID = :id
        ");
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'start_date' => $data['startDate'],
            'end_date' => $data['endDate'],
            'duration' => $data['duration'],
            'durationUnit' => $data['durationUnit'],
            'color' => $data['color'],
            'externalId' => $data['externalId'],
            'status' => $data['status'],
        ]);
    }

}