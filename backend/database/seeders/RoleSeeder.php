<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $commonRoles = [
            'Software Engineer',
            'Senior Software Engineer',
            'Lead Software Engineer',
            'Software Architect',
            'Frontend Developer',
            'Backend Developer',
            'Full Stack Developer',
            'DevOps Engineer',
            'Product Manager',
            'Senior Product Manager',
            'Product Owner',
            'Scrum Master',
            'Project Manager',
            'Data Analyst',
            'Data Scientist',
            'Data Engineer',
            'UX Designer',
            'UI Designer',
            'UX/UI Designer',
            'Product Designer',
            'Marketing Manager',
            'Sales Manager',
            'Business Analyst',
            'Quality Assurance Engineer',
            'Test Engineer',
            'Technical Writer',
            'System Administrator',
            'Database Administrator',
            'Security Engineer',
            'Mobile Developer',
            'iOS Developer',
            'Android Developer',
            'Machine Learning Engineer',
            'AI Engineer',
            'Cloud Engineer',
            'Site Reliability Engineer',
            'Technical Lead',
            'Engineering Manager',
            'CTO',
            'CEO',
            'CFO',
            'COO',
            'HR Manager',
            'Recruiter',
            'Customer Success Manager',
            'Support Engineer',
            'Sales Engineer',
            'Solution Architect',
            'Consultant',
            'Freelancer',
        ];

        foreach ($commonRoles as $roleName) {
            Role::firstOrCreate(['role_name' => $roleName]);
        }
    }
}
