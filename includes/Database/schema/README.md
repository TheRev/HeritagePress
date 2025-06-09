# HeritagePress Database Schema

## Core Tables

### hp_trees
- Primary genealogical tree container
- Each individual and family must belong to a tree
- Controls access and privacy at the tree level

Fields:
```sql
id (PK)
title
description
privacy_level
owner_user_id (FK to wp_users)
created_at
updated_at
```

### hp_individuals
- Stores basic information about each person
- Core demographic data
- Links to names, events, and relationships

Fields:
```sql
id (PK)
tree_id (FK to hp_trees)
uuid (Unique)
gender (enum: M/F/U/O)
birth_date (GEDCOM format)
birth_place_id (FK to hp_places)
death_date (GEDCOM format)
death_place_id (FK to hp_places)
living (boolean)
privacy_level (int)
created_at
updated_at
```

### hp_names
- Stores all name variations for individuals
- Supports multiple name types (birth, married, adopted, etc.)
- Enables cultural naming conventions

Fields:
```sql
id (PK)
individual_id (FK to hp_individuals)
type (primary/birth/married/adopted)
given
surname
prefix
suffix
nickname
sort_order
created_at
```

### hp_families
- Represents family units
- Links parents and children
- Stores marriage/divorce information

Fields:
```sql
id (PK)
tree_id (FK to hp_trees)
uuid (Unique)
marriage_date (GEDCOM format)
marriage_place_id (FK to hp_places)
divorce_date (GEDCOM format)
divorce_place_id (FK to hp_places)
privacy_level (int)
created_at
updated_at
```

### hp_family_links
- Links individuals to families
- Defines relationships within families

Fields:
```sql
id (PK)
family_id (FK to hp_families)
individual_id (FK to hp_individuals)
role (enum: parent1/parent2/child)
relationship_type (biological/adopted/foster)
created_at
```

## Foreign Key Constraints

1. hp_individuals
   - tree_id → hp_trees(id)
   - birth_place_id → hp_places(id)
   - death_place_id → hp_places(id)

2. hp_names
   - individual_id → hp_individuals(id)

3. hp_families
   - tree_id → hp_trees(id)
   - marriage_place_id → hp_places(id)
   - divorce_place_id → hp_places(id)

4. hp_family_links
   - family_id → hp_families(id)
   - individual_id → hp_individuals(id)

## Indexes

1. hp_individuals
   - PRIMARY KEY (id)
   - UNIQUE KEY uuid (uuid)
   - KEY tree_id (tree_id)
   - KEY birth_place_id (birth_place_id)
   - KEY death_place_id (death_place_id)
   - KEY living_privacy (living, privacy_level)

2. hp_names
   - PRIMARY KEY (id)
   - KEY individual_id (individual_id)
   - KEY surname_given (surname, given)
   - KEY type (type)

3. hp_families
   - PRIMARY KEY (id)
   - UNIQUE KEY uuid (uuid)
   - KEY tree_id (tree_id)
   - KEY marriage_place_id (marriage_place_id)
   - KEY divorce_place_id (divorce_place_id)

4. hp_family_links
   - PRIMARY KEY (id)
   - KEY family_id (family_id)
   - KEY individual_id (individual_id)
   - KEY role (role)

## Schema Version Control

The schema version will be tracked in the WordPress options table:
- Option name: 'heritagepress_db_version'
- Format: Matches plugin version (e.g., '1.0.0')

## Upgrade Process

1. Check current schema version against plugin version
2. Run appropriate upgrade scripts if needed
3. Update schema version in options
4. Log any errors during upgrade

## Data Validation

1. All dates must be in GEDCOM format
2. UUIDs must be unique across trees
3. Privacy levels must be 0-3 (0=public, 3=private)
4. Living status affects privacy calculations
5. Tree IDs must exist before referenced
6. Place IDs must exist before referenced

## Error Handling

1. Log all SQL errors
2. Implement rollback on failed upgrades
3. Verify foreign key constraints
4. Validate data before insertion
5. Check table existence before operations
