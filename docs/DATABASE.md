# Database Schema Documentation

## Tables

### Individuals
Stores information about individual people.
- `id` (Primary Key): Unique identifier
- `uuid`: Universal unique identifier
- `file_id`: Reference to the GEDCOM file
- `given_names`: First and middle names
- `surname`: Last name
- `birth_date`: Date of birth
- `birth_place_id`: Foreign key to Places table
- `death_date`: Date of death
- `death_place_id`: Foreign key to Places table
- `gender`: M/F/U (Male/Female/Unknown)
- `privacy`: Boolean flag for privacy settings
- `notes`: Text field for additional information
- `status`: 'active' or 'archived'

### Families
Represents family relationships.
- `id` (Primary Key): Unique identifier
- `uuid`: Universal unique identifier
- `file_id`: Reference to the GEDCOM file
- `husband_id`: Foreign key to Individuals table
- `wife_id`: Foreign key to Individuals table
- `marriage_date`: Date of marriage
- `marriage_place_id`: Foreign key to Places table
- `divorce_date`: Date of divorce (if applicable)
- `divorce_place_id`: Foreign key to Places table
- `notes`: Additional information
- `privacy`: Privacy flag
- `status`: 'active' or 'archived'

### Events
Tracks various life events.
- `id` (Primary Key): Unique identifier
- `uuid`: Universal unique identifier
- `file_id`: Reference to the GEDCOM file
- `individual_id`: Foreign key to Individuals table (optional)
- `family_id`: Foreign key to Families table (optional)
- `type`: Type of event (BIRTH, DEATH, MARRIAGE, etc.)
- `date`: Event date
- `place_id`: Foreign key to Places table
- `description`: Event description
- `privacy`: Privacy flag
- `status`: 'active' or 'archived'

### Places
Geographic locations.
- `id` (Primary Key): Unique identifier
- `uuid`: Universal unique identifier
- `file_id`: Reference to the GEDCOM file
- `name`: Place name
- `latitude`: Geographic latitude
- `longitude`: Geographic longitude
- `parent_id`: Self-referential foreign key for hierarchical places
- `notes`: Additional information
- `status`: 'active' or 'archived'

### Sources
Reference material and citations.
- `id` (Primary Key): Unique identifier
- `uuid`: Universal unique identifier
- `file_id`: Reference to the GEDCOM file
- `title`: Source title
- `author`: Author name
- `publication_info`: Publication details
- `repository`: Repository name
- `call_number`: Call number or reference
- `type`: Type of source
- `url`: Online reference (if applicable)
- `notes`: Additional information
- `date`: Source date
- `status`: 'active' or 'archived'

### Citations
Links between sources and various entities.
- `id` (Primary Key): Unique identifier
- `uuid`: Universal unique identifier
- `source_id`: Foreign key to Sources table
- `individual_id`: Foreign key to Individuals table (optional)
- `family_id`: Foreign key to Families table (optional)
- `event_id`: Foreign key to Events table (optional)
- `place_id`: Foreign key to Places table (optional)
- `page`: Page reference
- `quality_score`: Source quality rating
- `citation_text`: Citation text
- `notes`: Additional notes

### Media
Stores media file information.
- `id` (Primary Key): Unique identifier
- `uuid`: Universal unique identifier
- `title`: Media title
- `description`: Media description
- `file_path`: Path to media file
- `mime_type`: File type
- `privacy`: Privacy flag

### Media Relationships
Links media items to various entities.
- `id` (Primary Key): Unique identifier
- `media_id`: Foreign key to Media table
- `individual_id`: Foreign key to Individuals table (optional)
- `family_id`: Foreign key to Families table (optional)
- `event_id`: Foreign key to Events table (optional)
- `place_id`: Foreign key to Places table (optional)
- `source_id`: Foreign key to Sources table (optional)
- `notes`: Additional information

### Family Children
Many-to-many relationship between families and children.
- `id` (Primary Key): Unique identifier
- `family_id`: Foreign key to Families table
- `child_id`: Foreign key to Individuals table
- `relationship_type`: Type of relationship (birth, adoption, etc.)
- `notes`: Additional information

### GEDCOM Trees
Tracks imported GEDCOM files.
- `id` (Primary Key): Unique identifier
- `tree_id`: Universal unique identifier for the tree
- `file_name`: Original file name
- `title`: Tree title
- `description`: Tree description
- `version`: Version number
- `meta`: Additional metadata
- `status`: 'active' or 'archived'

## Database Version Management
The plugin uses a version management system to track and apply database updates:
- Version is stored in the WordPress options table as 'heritage_press_db_version'
- Current version: 1.0.0
- Updates are applied automatically when needed
- Each version update includes specific schema modifications
