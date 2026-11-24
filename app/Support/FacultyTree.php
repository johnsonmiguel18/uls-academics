<?php

namespace App\Support;

use App\Models\College;
use App\Models\FacultyMember;
use Illuminate\Support\Collection;

/**
 * A college's people as an organization chart, built from the data already in the admin (reviewers,
 * November 2026: "faculty default display as a hierarchy"): the dean at the top; under the dean the
 * Office of the Dean (assistant and associate deans), one unit per department with its chair, and the
 * faculty not in a department. Nothing new to enter: positions and departments decide where people go.
 */
class FacultyTree
{
    /** Positions read as the dean, a deputy of the dean, or a department chair. */
    private const DEAN = '/^\s*(college\s+)?dean\b/i';

    private const DEPUTY = '/\b(assistant|associate|vice)\s+dean\b/i';

    private const CHAIR = '/\b(chair(person)?|head|coordinator)\b/i';

    /**
     * @return array{root: FacultyMember|null, units: list<array{type: string, name: ?string, url: ?string, lead: ?FacultyMember, members: Collection}>}
     */
    public static function for(College $college): array
    {
        $faculty = $college->faculty;

        // The dean: a published profile with a dean position, else one made from the college's dean fields.
        $root = $faculty->first(fn ($p) => preg_match(self::DEAN, (string) $p->position) && ! preg_match(self::DEPUTY, (string) $p->position));
        if (! $root && $college->dean_name) {
            $root = new FacultyMember([
                'name' => $college->dean_name,
                'position' => $college->dean_title ?: null,
                'photo' => $college->dean_photo,
            ]);
        }
        $rest = $faculty->reject(fn ($p) => $root && $p->is($root));

        $units = [];
        $deputies = $rest->filter(fn ($p) => preg_match(self::DEPUTY, (string) $p->position))->values();
        if ($deputies->isNotEmpty()) {
            $units[] = ['type' => 'office', 'name' => null, 'url' => null, 'lead' => null, 'members' => $deputies];
        }
        $rest = $rest->reject(fn ($p) => $deputies->contains($p));

        $departments = $college->relationLoaded('departments') ? $college->departments : collect();
        foreach ($departments as $department) {
            $people = $rest->where('department_id', $department->id)->values();
            $lead = $people->first(fn ($p) => preg_match(self::CHAIR, (string) $p->position));
            $units[] = [
                'type' => 'department',
                'name' => $department->name,
                'url' => route('academics.department', [$college, $department]),
                'lead' => $lead,
                'members' => $people->reject(fn ($p) => $lead && $p->is($lead))->values(),
            ];
        }

        $unassigned = $rest->reject(fn ($p) => $departments->contains('id', $p->department_id))->values();
        if ($unassigned->isNotEmpty()) {
            $units[] = ['type' => 'faculty', 'name' => null, 'url' => null, 'lead' => null, 'members' => $unassigned];
        }

        return ['root' => $root, 'units' => $units];
    }
}
