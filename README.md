# citas
HCI AND DIGITAL CITIZENSHIP
Estructura del Backend
/backend
│── config/
│    └── Database.php             # Conexión MySQL
│
│── models/
│    ├── Paciente.php        # Modelo de pacientes
│    └── Cita.php    # Modelo de citas
|     └── Medico.php    # Modelo de medicos
|     └── Especialidad.php    # Modelo de especialidades
│
│── controllers/
│    ├── AuthController.php
│    └── BaseController.php
│    └── CitaController.php
│    └── PacienteController.php
│
│── Api/
│    └── test.php # Prueba unitaria simple

Estructura (Frontend)
citas/
├─ index.html
├─ dashboard.html
├─ registro-paciente.html
├─ programar-cita.html
├─ historial.html
├─ css/
│  └─ styles.css
└─ js/
   └─ ui.js

