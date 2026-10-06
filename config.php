<?php
// Mot de passe de l'interface d'administration : À CHANGER avant mise en ligne
const ADMIN_PASSWORD = 'changeme';

// Nombre maximum d'images en compétition
const MAX_IMAGES = 5;

// Valeur initiale du blocage du vote par adresse IP (en plus du cookie),
// ensuite activable / désactivable depuis l'admin.
// Attention : sur un réseau partagé (bureau, wifi public), une seule personne pourra voter.
const VOTE_CHECK_IP = false;

// Taille max d'un upload (octets)
const MAX_UPLOAD_SIZE = 8 * 1024 * 1024;

const DATA_FILE  = __DIR__ . '/data/data.json';
const UPLOAD_DIR = __DIR__ . '/uploads';
