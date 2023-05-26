<?php

/**
 * Class Validator
 *
 * This class is used to perform data validation operations.
 *
 */

class Validator
{

    /**
     * Validates the given name and checks the maximum character limit.
     *
     * @param string $name The name to be validated.
     * @throws Exception Throws an exception if the name exceeds 255 characters.
     */
    public static function validateName($name)
    {
        if (strlen($name) > 255) {
            throw new Exception('Name cannot exceed 255 characters.');
        }
    }

    /**
     * Validates the given start date and checks if it is a valid ISO8601 format.
     *
     * @param string $startDate The start date to be validated.
     * @throws Exception Throws an exception if the start date is not a valid ISO8601 format.
     */
    public static function validateStartDate($startDate)
    {
        if (empty($startDate)) {
        throw new Exception('Start date is required.');
    }
        $dateTime = DateTime::createFromFormat(DateTime::ATOM, $startDate);
        if (!$dateTime) {
            throw new Exception('Invalid start date format. Use ISO8601 format (e.g., 2022-12-31T14:59:00Z).');
        }
    }

    /**
     * Validates the given end date and checks if it is a valid ISO8601 format or null.
     *
     * @param string|null $endDate The end date to be validated or null.
     * @param string $startDate The start date for comparison.
     * @throws Exception Throws an exception if the end date is not a valid ISO8601 format or not null or if it is not later than the start date.
     */
    public static function validateEndDate($endDate, $startDate)
    {
        if ($endDate !== null) {
            $startDateTime = new DateTime($startDate);
            $endDateTime = new DateTime($endDate);

            if (!$endDateTime || $endDateTime <= $startDateTime) {
                throw new Exception('Invalid end date. It should be either null or a valid datetime that is later than the start date.');
            }
        }
    }


    /**
     * Validates the duration unit value.
     *
     * @param string $durationUnit The duration unit value to validate.
     * @throws Exception If the duration unit value is invalid.
     */
    public static function validateDurationUnit($durationUnit)
    {
        $validDurationUnits = ['HOURS', 'DAYS', 'WEEKS'];
        if ($durationUnit !== null && !in_array($durationUnit, $validDurationUnits)) {
            throw new Exception('Invalid duration unit value. Valid values are: ' . implode(', ', $validDurationUnits));
        }
    }

    /**
     * Validates the given color and checks if it is a valid HEX color format or null.
     *
     * @param string|null $color The color to be validated or null.
     * @throws Exception Throws an exception if the color is not a valid HEX color format or not null.
     */
    public static function validateColor($color)
    {
        if ($color === '') {
            // Boş renk değeri geçerlidir, hata fırlatma
            return;
        }
        if ($color !== null && !preg_match('/^#([A-Fa-f0-9]{6})$/', $color)) {
            throw new Exception('Invalid color format. Use valid HEX color format (e.g., #FF0000) or set it as null.');
        }
    }

    /**
     * Validates the given external ID and checks the maximum character limit.
     *
     * @param string|null $externalId The external ID to be validated or null.
     * @throws Exception Throws an exception if the external ID exceeds 255 characters.
     */
    public static function validateExternalId($externalId)
    {
        if ($externalId !== null && !is_string($externalId)) {
            throw new Exception('External ID must be a string or null.');
        }
    
        if ($externalId !== null && strlen($externalId) > 255) {
            throw new Exception('External ID cannot exceed 255 characters.');
        }
    }

    /**
     * Validates the given status and checks if it is a valid status value.
     * The default value is set to "NEW".
     *
     * @param string $status The status to be validated.
     * @throws Exception Throws an exception if the status is not a valid value.
     */
    public static function validateStatus($status)
    {
        $validStatuses = ['NEW', 'PLANNED', 'DELETED'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception('Invalid status value. Valid values are: ' . implode(', ', $validStatuses));
        }
    }


}   

?>
