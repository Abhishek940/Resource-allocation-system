<?php

namespace App\Repositories;

use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
class ProjectRepository implements ProjectRepositoryInterface
{
    protected string $table = 'projects';

    public function getAll(int $perPage = 10): LengthAwarePaginator
    {
      /*   return Project::query()
            ->latest('id')
            ->paginate($perPage); */

       return DB::table('projects')
        ->where('deleted_at', '=', 0)
        ->orderByDesc('id')
        ->paginate($perPage);
     

    }

    public function findById(int $id): ?Project
    {
        return Project::find($id);
    }

    public function createProject(array $data):int
    {
        return DB::table($this->table)->insertGetId($data);
    }

    public function update(int $id, array $data): ?array
    {
        DB::table('projects')->where('id', $id)->update($data);

    return (array) DB::table('projects')
        ->where('id', $id)->first();
    }

    public function delete(int $id): bool
{
    return DB::table('projects')->where('id', $id)
        ->update([
            'deleted_at' => 1,
            'updated_at' => now(),
        ]) > 0;
}

 public function getDropdownProject(){
        return DB:: table('projects')->select('id', 'projectCode','projectName')->get();
    }

}