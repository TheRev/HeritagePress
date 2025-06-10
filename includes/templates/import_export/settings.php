// Get saved settings or use defaults
$import_settings = get_option('heritagepress_import_settings', array(
    'default_privacy_living' => true,
    'default_import_media' => true,
    'default_import_option' => 'replace',
    'default_character_encoding' => 'UTF-8'
));

$export_settings = get_option('heritagepress_export_settings', array(
    'default_privacy_living' => true,
    'default_privacy_notes' => false,
    'default_privacy_media' => false,
    'default_gedcom_version' => '5.5.1',
    'default_export_format' => 'gedcom',
    'default_character_encoding' => 'UTF-8'
));