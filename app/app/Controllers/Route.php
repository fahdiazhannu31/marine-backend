<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Routes;

class Route extends BaseController
{
    public function getRoutes()
    {
        $routeModel = new Routes();
        // Mengambil semua route
        $routes = $routeModel->getRoutes();

        if ($routes) {
            return $this->response->setJSON([
                'status' => 'success',
                'data'   => $routes
            ]);
        } else {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'No routes found'
            ]);
        }
    }
}
