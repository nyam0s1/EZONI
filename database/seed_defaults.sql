-- Editable starter choices for a fresh installation, not recovered farm data.
-- Only missing choices are added, and existing species thresholds are preserved.

INSERT INTO system_options (option_type, option_value)
SELECT choices.option_type, choices.option_value
FROM (
    SELECT 'stock_category' AS option_type, 'Feeds' AS option_value
    UNION ALL SELECT 'stock_category', 'Meds'
    UNION ALL SELECT 'stock_category', 'Tools'
    UNION ALL SELECT 'produce_category', 'Dairy'
    UNION ALL SELECT 'produce_category', 'Crops'
    UNION ALL SELECT 'produce_category', 'Eggs'
    UNION ALL SELECT 'animal_species', 'Cattle'
    UNION ALL SELECT 'animal_species', 'Goats'
    UNION ALL SELECT 'animal_species', 'Sheep'
    UNION ALL SELECT 'animal_species', 'Chicken'
    UNION ALL SELECT 'animal_species', 'Duck'
) AS choices
WHERE NOT EXISTS (
    SELECT 1 FROM system_options AS existing
    WHERE existing.option_type = choices.option_type
      AND LOWER(existing.option_value) = LOWER(choices.option_value)
);

INSERT INTO species_limits (species, min_threshold)
SELECT choices.species, 5
FROM (
    SELECT 'Cattle' AS species
    UNION ALL SELECT 'Goats'
    UNION ALL SELECT 'Sheep'
    UNION ALL SELECT 'Chicken'
    UNION ALL SELECT 'Duck'
) AS choices
WHERE NOT EXISTS (
    SELECT 1 FROM species_limits AS existing WHERE LOWER(existing.species) = LOWER(choices.species)
);
