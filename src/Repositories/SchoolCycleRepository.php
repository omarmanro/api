<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\SchoolCycle;

class SchoolCycleRepository extends BaseRepository
{
    protected string $model = SchoolCycle::class;

    public function getActive(): ?SchoolCycle
    {
        $cycles = $this->all(['status' => SchoolCycle::STATUS_ACTIVE], ['start_date' => 'DESC'], 1);
        return $cycles[0] ?? null;
    }

    public function getUpcoming(): array
    {
        return $this->all(['status' => SchoolCycle::STATUS_UPCOMING], ['start_date' => 'ASC']);
    }

    public function getClosed(): array
    {
        return $this->all(['status' => SchoolCycle::STATUS_CLOSED], ['start_date' => 'DESC']);
    }

    public function findByName(string $name): ?SchoolCycle
    {
        return $this->findBy('name', $name);
    }

    public function activate(int $id): ?SchoolCycle
    {
        // Desactivar todos los ciclos activos primero
        $this->db->update(
            $this->table,
            ['status' => SchoolCycle::STATUS_CLOSED],
            "status = :status",
            ['status' => SchoolCycle::STATUS_ACTIVE]
        );

        // Activar el ciclo seleccionado
        return $this->update($id, ['status' => SchoolCycle::STATUS_ACTIVE]);
    }
}
