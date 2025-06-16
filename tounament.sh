#!/bin/bash
# create-tournament-enums.sh

# Set the base directory for enums
ENUM_DIR="app/Tournaments/Enums"

# Create enum directory if it doesn't exist
mkdir -p $ENUM_DIR

echo "Creating tournament system enums..."

# Create TournamentStage enum
cat > "${ENUM_DIR}/TournamentStage.php" << 'EOF'
<?php

namespace App\Tournaments\Enums;

enum TournamentStage: string
{
   case REGISTRATION = 'registration';
   case SEEDING = 'seeding';
   case GROUP = 'group';
   case BRACKET = 'bracket';
   case COMPLETED = 'completed';
}
EOF

# Create TournamentType enum
cat > "${ENUM_DIR}/TournamentType.php" << 'EOF'
<?php

namespace App\Tournaments\Enums;

enum TournamentType: string
{
   case SINGLE_ELIMINATION = 'single_elimination';
   case DOUBLE_ELIMINATION = 'double_elimination';
   case DOUBLE_ELIMINATION_FULL = 'double_elimination_full';
   case ROUND_ROBIN = 'round_robin';
   case GROUPS = 'groups';
   case GROUPS_PLAYOFF = 'groups_playoff';
   case TEAM_GROUPS_PLAYOFF = 'team_groups_playoff';
}
EOF

# Create SeedingMethod enum
cat > "${ENUM_DIR}/SeedingMethod.php" << 'EOF'
<?php

namespace App\Tournaments\Enums;

enum SeedingMethod: string
{
   case RANDOM = 'random';
   case RATING = 'rating';
   case MANUAL = 'manual';
}
EOF

# Create TournamentStatus enum
cat > "${ENUM_DIR}/TournamentStatus.php" << 'EOF'
<?php

namespace App\Tournaments\Enums;

enum TournamentStatus: string
{
   case UPCOMING = 'upcoming';
   case ACTIVE = 'active';
   case COMPLETED = 'completed';
   case CANCELLED = 'cancelled';
}
EOF

# Create TournamentPlayerStatus enum
cat > "${ENUM_DIR}/TournamentPlayerStatus.php" << 'EOF'
<?php

namespace App\Tournaments\Enums;

enum TournamentPlayerStatus: string
{
   case APPLIED = 'applied';
   case CONFIRMED = 'confirmed';
   case REJECTED = 'rejected';
   case ELIMINATED = 'eliminated';
   case DNF = 'dnf';
}
EOF

# Create EliminationRound enum
cat > "${ENUM_DIR}/EliminationRound.php" << 'EOF'
<?php

namespace App\Tournaments\Enums;

enum EliminationRound: string
{
   case GROUPS = 'groups';
   case ROUND_128 = 'round_128';
   case ROUND_64 = 'round_64';
   case ROUND_32 = 'round_32';
   case ROUND_16 = 'round_16';
   case QUARTERFINALS = 'quarterfinals';
   case SEMIFINALS = 'semifinals';
   case FINALS = 'finals';
   case THIRD_PLACE = 'third_place';
}
EOF

# Create MatchStage enum
cat > "${ENUM_DIR}/MatchStage.php" << 'EOF'
<?php

namespace App\Tournaments\Enums;

enum MatchStage: string
{
   case BRACKET = 'bracket';
   case GROUP = 'group';
   case THIRD_PLACE = 'third_place';
}
EOF

# Create MatchStatus enum
cat > "${ENUM_DIR}/MatchStatus.php" << 'EOF'
<?php

namespace App\Tournaments\Enums;

enum MatchStatus: string
{
   case PENDING = 'pending';
   case READY = 'ready';
   case IN_PROGRESS = 'in_progress';
   case VERIFICATION = 'verification';
   case COMPLETED = 'completed';
   case CANCELLED = 'cancelled';
}
EOF

# Create BracketSide enum
cat > "${ENUM_DIR}/BracketSide.php" << 'EOF'
<?php

namespace App\Tournaments\Enums;

enum BracketSide: string
{
   case UPPER = 'upper';
   case LOWER = 'lower';
}
EOF

# Create BracketType enum
cat > "${ENUM_DIR}/BracketType.php" << 'EOF'
<?php

namespace App\Tournaments\Enums;

enum BracketType: string
{
   case SINGLE = 'single';
   case DOUBLE_UPPER = 'double_upper';
   case DOUBLE_LOWER = 'double_lower';
}
EOF

# Make the script executable
chmod +x create-tournament-enums.sh

echo "✅ All tournament enums created successfully!"
echo ""
echo "Created enums:"
echo "  - TournamentStage.php"
echo "  - TournamentType.php"
echo "  - SeedingMethod.php"
echo "  - TournamentStatus.php"
echo "  - TournamentPlayerStatus.php"
echo "  - EliminationRound.php"
echo "  - MatchStage.php"
echo "  - MatchStatus.php"
echo "  - BracketSide.php"
echo "  - BracketType.php"
echo ""
echo "Don't forget to update your models with the new enum casts!"
