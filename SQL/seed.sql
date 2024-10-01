DELETE FROM profesionales;

DELETE FROM user_groups;

DELETE FROM `user`;

DELETE FROM turnos;

-- Inserción de grupos de usuarios
INSERT INTO
    user_groups (id, group_name)
VALUES (1, 'Admin'),
    (2, 'Profesional'),
    (3, 'Paciente');

-- Inserción de usuarios
INSERT INTO
    user (
        user_group_id,
        username,
        password,
        email
    )
VALUES (
        1,
        'admin',
        '$2y$10$Ev84qeQaB3V9ZG6w4EeiFesrlCPVJ7DHaVAz5jl69F2ed.FDqogXS',
        'admin@example.com'
    ),
    (
        2,
        'profesional1',
        '$2y$10$Ev84qeQaB3V9ZG6w4EeiFesrlCPVJ7DHaVAz5jl69F2ed.FDqogXS',
        'pro1@example.com'
    ),
    (
        2,
        'profesional2',
        '$2y$10$Ev84qeQaB3V9ZG6w4EeiFesrlCPVJ7DHaVAz5jl69F2ed.FDqogXS',
        'pro2@example.com'
    ),
    (
        3,
        'paciente1',
        '$2y$10$Ev84qeQaB3V9ZG6w4EeiFesrlCPVJ7DHaVAz5jl69F2ed.FDqogXS',
        'pac1@example.com'
    ),
    (
        3,
        'paciente2',
        '$2y$10$Ev84qeQaB3V9ZG6w4EeiFesrlCPVJ7DHaVAz5jl69F2ed.FDqogXS',
        'pac2@example.com'
    );

-- Inserción de profesionales
INSERT INTO
    profesionales (
        user_id,
        nombre,
        apellido,
        especialidad
    )
VALUES (
        2,
        'Juan',
        'Pérez',
        'Cardiología'
    ),
    (
        3,
        'Ana',
        'García',
        'Dermatología'
    );

-- Inserción de turnos
INSERT INTO
    turnos (
        profesional_id,
        user_id,
        fecha,
        hora,
        estado
    )
VALUES (
        1,
        4,
        '2024-09-20',
        '10:00:00',
        'pendiente'
    ),
    (
        1,
        5,
        '2024-09-21',
        '11:00:00',
        'confirmado'
    ),
    (
        2,
        4,
        '2024-09-22',
        '09:30:00',
        'cancelado'
    );