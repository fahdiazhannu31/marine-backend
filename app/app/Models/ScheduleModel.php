<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleModel extends Model
{
    protected $table            = 'schedule';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['boat_id', 'type', 'date', 'total_pax'];

    public function getDeparture()
    {
        $this->db->table('boat')
            ->select('*')
            ->where('type', 'DEPARTURE')
            ->get()
            ->getResult();
    }

    public function getReturn()
    {
        $this->db->table('boat')
            ->select('*')
            ->where('type', 'RETURN')
            ->get()
            ->getResult();
    }

    public function getScheduleDate()
    {
        // Get the query builder instance for the 'schedule' table
        $builder = $this->db->table('schedule a');

        // Build the query with select and join
        $builder->select('a.id, a.type, a.date, a.total_pax, b.boat_name')
            ->join('boat b', 'b.id = a.boat_id', 'left');

        // Execute the query and return the result
        return $builder->get()->getResultArray(); // Return the result as an array of objects
    }
}
