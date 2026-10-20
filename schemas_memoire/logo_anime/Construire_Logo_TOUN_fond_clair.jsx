// =====================================================================
//  Animation du logo TOUN - construction automatique du projet After Effects
//  Utilisation : After Effects > Fichier > Scripts > Exécuter le fichier de script...
//  Le script doit rester dans le même dossier que les dossiers "elements" et "audio".
// =====================================================================

(function () {
    var DATA = {"titre": "Logo TOUN anime", "largeur": 1920, "hauteur": 1080, "ips": 30, "duree": 6.0, "scenes": [{"nom": "Logo TOUN", "debut": 0, "duree": 6.0, "fond": "#f6fafd", "calques": [{"nom": "Lueur", "type": "image", "src": "elements/lueur.png", "l": 1500, "h": 1500, "echelle_base": 93.75, "pos": [1129.0, 639.56], "decor": true, "opacite": 0, "anim": {"position": [[1.75, [960, 640], "lin"], [2.4, [1129.0, 639.56], "io"]], "opacite": [[0.9, 0, "lin"], [1.6, 45, "lin"], [3.2, 27.0, "io"]], "echelle": [[0.9, 30, "lin"], [1.8, 100, "out"], [6.0, 112, "lin"]]}, "debut": 0, "fin": 6.0}, {"nom": "lettre_O", "type": "image", "src": "elements/lettre_O.png", "l": 308.56, "h": 300.28, "echelle_base": 45.9851, "pivot": [154.28, 150.14], "pos": [763.64, 489.88], "anim": {"position": [[2.05, [933.64, 489.88], "lin"], [2.65, [763.64, 489.88], "out"]], "echelle": [[2.05, 70, "lin"], [2.422, 103.6, "out"], [2.65, 100, "io"], [3.6, 100, "lin"], [6.0, 103, "lin"]], "opacite": [[2.05, 0, "lin"], [2.3, 100, "lin"]], "flou": [[2.05, 22, "lin"], [2.55, 0, "out"]]}, "libelle": "Lettre O", "debut": 2.05, "fin": 6.0, "opacite": 100}, {"nom": "lettre_T", "type": "image", "src": "elements/lettre_T.png", "l": 260.74, "h": 293.85, "echelle_base": 45.9851, "pivot": [130.37, 146.92], "pos": [440.37, 490.34], "anim": {"position": [[2.2, [700.37, 490.34], "lin"], [2.8, [440.37, 490.34], "out"]], "echelle": [[2.2, 70, "lin"], [2.572, 103.6, "out"], [2.8, 100, "io"], [3.6, 100, "lin"], [6.0, 103, "lin"]], "opacite": [[2.2, 0, "lin"], [2.45, 100, "lin"]], "flou": [[2.2, 22, "lin"], [2.7, 0, "out"]]}, "libelle": "Lettre T", "debut": 2.2, "fin": 6.0, "opacite": 100}, {"nom": "lettre_N", "type": "image", "src": "elements/lettre_N.png", "l": 257.98, "h": 292.93, "echelle_base": 45.9851, "pivot": [128.99, 146.46], "pos": [1481.01, 491.26], "anim": {"position": [[2.1, [1281.01, 491.26], "lin"], [2.7, [1481.01, 491.26], "out"]], "echelle": [[2.1, 70, "lin"], [2.472, 103.6, "out"], [2.7, 100, "io"], [3.6, 100, "lin"], [6.0, 103, "lin"]], "opacite": [[2.1, 0, "lin"], [2.35, 100, "lin"]], "flou": [[2.1, 22, "lin"], [2.6, 0, "out"]]}, "libelle": "Lettre N", "debut": 2.1, "fin": 6.0, "opacite": 100}, {"nom": "Livre - base", "type": "image", "src": "elements/livre_base.png", "l": 283.27, "h": 51.04, "echelle_base": 45.9851, "pivot": [141.63, 25.29], "pos": [960.0, 630.91], "anim": {"echelle": [[0.2, [0.0, 180.0], "lin"], [0.51, [201.6, 180.0], "out"], [0.7, [180.0, 180.0], "io"], [1.75, [180.0, 180.0], "lin"], [2.4, [100, 100], "io"], [6.0, [103, 103], "lin"]], "opacite": [[0.2, 0, "lin"], [0.3, 100, "lin"]], "position": [[1.75, [960.0, 630.91], "lin"], [2.4, [1129.0, 634.51], "io"]]}, "debut": 0, "fin": 6.0, "opacite": 100}, {"nom": "Livre - page gauche", "type": "image", "src": "elements/livre_page_gauche.png", "l": 123.7, "h": 61.16, "echelle_base": 45.9851, "pivot": [145.31, 69.9], "pos": [960.0, 640.0], "anim": {"rotation": [[0.65, 78, "lin"], [0.991, -9.36, "out"], [1.2, 0, "io"]], "opacite": [[0.65, 0, "lin"], [0.77, 100, "lin"]], "echelle": [[0.65, 162.0, "lin"], [1.2, 180.0, "out"], [1.75, 180.0, "lin"], [2.4, 100, "io"], [6.0, 103, "lin"]], "position": [[1.75, [960.0, 640.0], "lin"], [2.4, [1129.0, 639.56], "io"]]}, "debut": 0.65, "fin": 6.0, "opacite": 100}, {"nom": "Livre - page droite", "type": "image", "src": "elements/livre_page_droite.png", "l": 125.54, "h": 61.16, "echelle_base": 45.9851, "pivot": [-19.31, 69.9], "pos": [960.0, 640.0], "anim": {"rotation": [[0.8, -78, "lin"], [1.141, 9.36, "out"], [1.35, 0, "io"]], "opacite": [[0.8, 0, "lin"], [0.92, 100, "lin"]], "echelle": [[0.8, 162.0, "lin"], [1.35, 180.0, "out"], [1.75, 180.0, "lin"], [2.4, 100, "io"], [6.0, 103, "lin"]], "position": [[1.75, [960.0, 640.0], "lin"], [2.4, [1129.0, 639.56], "io"]]}, "debut": 0.8, "fin": 6.0, "opacite": 100}, {"nom": "Livre - couverture gauche (U)", "type": "image", "src": "elements/livre_couverture_gauche.png", "l": 136.58, "h": 277.29, "echelle_base": 45.9851, "pivot": [145.31, 292.47], "pos": [960.0, 640.0], "anim": {"rotation": [[1.05, 60, "lin"], [1.453, -7.2, "out"], [1.7, 0, "io"]], "echelle": [[1.05, 36.0, "lin"], [1.453, 197.28, "out"], [1.7, 180.0, "io"], [1.75, 180.0, "lin"], [2.4, 100, "io"], [6.0, 103, "lin"]], "opacite": [[1.05, 0, "lin"], [1.2, 100, "lin"]], "position": [[1.75, [960.0, 640.0], "lin"], [2.4, [1129.0, 639.56], "io"]]}, "debut": 1.05, "fin": 6.0, "opacite": 100}, {"nom": "Livre - couverture droite (U)", "type": "image", "src": "elements/livre_couverture_droite.png", "l": 137.04, "h": 275.91, "echelle_base": 45.9851, "pivot": [-7.82, 291.09], "pos": [960.0, 640.0], "anim": {"rotation": [[1.15, -60, "lin"], [1.553, 7.2, "out"], [1.8, 0, "io"]], "echelle": [[1.15, 36.0, "lin"], [1.75, 36.0, "lin"], [2.4, 100, "io"], [6.0, 103, "lin"]], "opacite": [[1.15, 0, "lin"], [1.3, 100, "lin"]], "position": [[1.75, [960.0, 640.0], "lin"], [2.4, [1129.0, 639.56], "io"]]}, "debut": 1.15, "fin": 6.0, "opacite": 100}, {"nom": "Particule 1", "type": "ellipse", "l": 15, "h": 15, "couleur": "#0980c2", "pos": [960.0, 540.7], "debut": 1.6571415981588369, "fin": 3.329661654238656, "anim": {"position": [[1.657, [960.0, 540.7], "lin"], [2.075, [988.4, 255.8], "lin"], [2.493, [983.5, 52.3], "lin"], [2.912, [973.8, -69.8], "lin"], [3.33, [996.8, -110.5], "lin"]], "opacite": [[1.657, 0, "lin"], [1.807, 90, "lin"], [2.661, 80, "lin"], [3.33, 0, "lin"]], "echelle": [[1.657, 40, "lin"], [1.957, 100, "out"], [3.33, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 2", "type": "ellipse", "l": 19, "h": 19, "couleur": "#1fa6b8", "pos": [975.1, 540.7], "debut": 1.7786463459649071, "fin": 3.5825525221383487, "anim": {"position": [[1.779, [975.1, 540.7], "lin"], [2.23, [915.6, 324.3], "lin"], [2.681, [836.0, 169.8], "lin"], [3.132, [781.7, 77.1], "lin"], [3.583, [751.1, 46.1], "lin"]], "opacite": [[1.779, 0, "lin"], [1.929, 90, "lin"], [2.861, 80, "lin"], [3.583, 0, "lin"]], "echelle": [[1.779, 40, "lin"], [2.079, 100, "out"], [3.583, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 3", "type": "ellipse", "l": 18, "h": 18, "couleur": "#67e0e6", "pos": [976.4, 540.7], "debut": 1.3347111106062965, "fin": 3.282426738100039, "anim": {"position": [[1.335, [976.4, 540.7], "lin"], [1.822, [919.3, 394.3], "lin"], [2.309, [873.9, 289.8], "lin"], [2.795, [860.7, 227.1], "lin"], [3.282, [840.2, 206.2], "lin"]], "opacite": [[1.335, 0, "lin"], [1.485, 90, "lin"], [2.503, 80, "lin"], [3.282, 0, "lin"]], "echelle": [[1.335, 40, "lin"], [1.635, 100, "out"], [3.282, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 4", "type": "ellipse", "l": 17, "h": 17, "couleur": "#2f55a4", "pos": [962.5, 540.7], "debut": 1.2876923027328615, "fin": 3.110830329559934, "anim": {"position": [[1.288, [962.5, 540.7], "lin"], [1.743, [1026.8, 249.0], "lin"], [2.199, [1123.7, 40.7], "lin"], [2.655, [1230.0, -84.3], "lin"], [3.111, [1305.0, -125.9], "lin"]], "opacite": [[1.288, 0, "lin"], [1.438, 90, "lin"], [2.382, 80, "lin"], [3.111, 0, "lin"]], "echelle": [[1.288, 40, "lin"], [1.588, 100, "out"], [3.111, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 5", "type": "ellipse", "l": 11, "h": 11, "couleur": "#0980c2", "pos": [946.4, 540.7], "debut": 1.3917446856261462, "fin": 2.73938556976123, "anim": {"position": [[1.392, [946.4, 540.7], "lin"], [1.729, [862.4, 321.6], "lin"], [2.066, [802.1, 165.1], "lin"], [2.402, [719.7, 71.2], "lin"], [2.739, [610.7, 39.9], "lin"]], "opacite": [[1.392, 0, "lin"], [1.542, 90, "lin"], [2.2, 80, "lin"], [2.739, 0, "lin"]], "echelle": [[1.392, 40, "lin"], [1.692, 100, "out"], [2.739, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 6", "type": "ellipse", "l": 20, "h": 20, "couleur": "#1fa6b8", "pos": [942.7, 540.7], "debut": 1.4677487122986932, "fin": 3.120173605624019, "anim": {"position": [[1.468, [942.7, 540.7], "lin"], [1.881, [881.6, 332.3], "lin"], [2.294, [813.3, 183.5], "lin"], [2.707, [711.9, 94.1], "lin"], [3.12, [613.1, 64.4], "lin"]], "opacite": [[1.468, 0, "lin"], [1.618, 90, "lin"], [2.459, 80, "lin"], [3.12, 0, "lin"]], "echelle": [[1.468, 40, "lin"], [1.768, 100, "out"], [3.12, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 7", "type": "ellipse", "l": 14, "h": 14, "couleur": "#67e0e6", "pos": [955.0, 540.7], "debut": 1.717211703287669, "fin": 3.5471713287999638, "anim": {"position": [[1.717, [955.0, 540.7], "lin"], [2.175, [1001.4, 326.3], "lin"], [2.632, [1016.5, 173.2], "lin"], [3.09, [1017.8, 81.4], "lin"], [3.547, [1048.5, 50.8], "lin"]], "opacite": [[1.717, 0, "lin"], [1.867, 90, "lin"], [2.815, 80, "lin"], [3.547, 0, "lin"]], "echelle": [[1.717, 40, "lin"], [2.017, 100, "out"], [3.547, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 8", "type": "ellipse", "l": 18, "h": 18, "couleur": "#2f55a4", "pos": [971.8, 540.7], "debut": 1.5003466096974973, "fin": 3.472519049291787, "anim": {"position": [[1.5, [971.8, 540.7], "lin"], [1.993, [1061.5, 243.9], "lin"], [2.486, [1124.4, 31.9], "lin"], [2.979, [1205.5, -95.3], "lin"], [3.473, [1315.9, -137.7], "lin"]], "opacite": [[1.5, 0, "lin"], [1.65, 90, "lin"], [2.684, 80, "lin"], [3.473, 0, "lin"]], "echelle": [[1.5, 40, "lin"], [1.8, 100, "out"], [3.473, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 9", "type": "ellipse", "l": 19, "h": 19, "couleur": "#0980c2", "pos": [977.8, 540.7], "debut": 1.533749495313995, "fin": 2.889928294961936, "anim": {"position": [[1.534, [977.8, 540.7], "lin"], [1.873, [911.3, 361.4], "lin"], [2.212, [847.4, 233.3], "lin"], [2.551, [816.9, 156.5], "lin"], [2.89, [788.6, 130.8], "lin"]], "opacite": [[1.534, 0, "lin"], [1.684, 90, "lin"], [2.347, 80, "lin"], [2.89, 0, "lin"]], "echelle": [[1.534, 40, "lin"], [1.834, 100, "out"], [2.89, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 10", "type": "ellipse", "l": 20, "h": 20, "couleur": "#1fa6b8", "pos": [967.4, 540.7], "debut": 1.610359824426642, "fin": 3.6767937310824927, "anim": {"position": [[1.61, [967.4, 540.7], "lin"], [2.127, [1004.8, 345.2], "lin"], [2.644, [1071.6, 205.5], "lin"], [3.16, [1156.4, 121.7], "lin"], [3.677, [1214.3, 93.8], "lin"]], "opacite": [[1.61, 0, "lin"], [1.76, 90, "lin"], [2.85, 80, "lin"], [3.677, 0, "lin"]], "echelle": [[1.61, 40, "lin"], [1.91, 100, "out"], [3.677, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 11", "type": "ellipse", "l": 22, "h": 22, "couleur": "#67e0e6", "pos": [950.2, 540.7], "debut": 1.2504904433500135, "fin": 2.9264802641609466, "anim": {"position": [[1.25, [950.2, 540.7], "lin"], [1.669, [893.4, 258.1], "lin"], [2.088, [866.0, 56.2], "lin"], [2.507, [824.4, -64.9], "lin"], [2.926, [751.6, -105.2], "lin"]], "opacite": [[1.25, 0, "lin"], [1.4, 90, "lin"], [2.256, 80, "lin"], [2.926, 0, "lin"]], "echelle": [[1.25, 40, "lin"], [1.55, 100, "out"], [2.926, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 12", "type": "ellipse", "l": 12, "h": 12, "couleur": "#2f55a4", "pos": [942.0, 540.7], "debut": 1.607681949271353, "fin": 3.530490636212674, "anim": {"position": [[1.608, [942.0, 540.7], "lin"], [2.088, [884.1, 304.8], "lin"], [2.569, [828.4, 136.3], "lin"], [3.05, [739.6, 35.2], "lin"], [3.53, [643.8, 1.5], "lin"]], "opacite": [[1.608, 0, "lin"], [1.758, 90, "lin"], [2.761, 80, "lin"], [3.53, 0, "lin"]], "echelle": [[1.608, 40, "lin"], [1.908, 100, "out"], [3.53, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 13", "type": "ellipse", "l": 10, "h": 10, "couleur": "#0980c2", "pos": [950.3, 540.7], "debut": 1.3284297785169532, "fin": 3.2348621921119607, "anim": {"position": [[1.328, [950.3, 540.7], "lin"], [1.805, [945.2, 249.1], "lin"], [2.282, [913.1, 40.9], "lin"], [2.758, [859.3, -84.0], "lin"], [3.235, [829.3, -125.7], "lin"]], "opacite": [[1.328, 0, "lin"], [1.478, 90, "lin"], [2.472, 80, "lin"], [3.235, 0, "lin"]], "echelle": [[1.328, 40, "lin"], [1.628, 100, "out"], [3.235, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 14", "type": "ellipse", "l": 10, "h": 10, "couleur": "#1fa6b8", "pos": [967.6, 540.7], "debut": 1.471749154003817, "fin": 3.4093663634789966, "anim": {"position": [[1.472, [967.6, 540.7], "lin"], [1.956, [907.0, 399.5], "lin"], [2.441, [815.2, 298.6], "lin"], [2.925, [733.0, 238.1], "lin"], [3.409, [683.4, 217.9], "lin"]], "opacite": [[1.472, 0, "lin"], [1.622, 90, "lin"], [2.634, 80, "lin"], [3.409, 0, "lin"]], "echelle": [[1.472, 40, "lin"], [1.772, 100, "out"], [3.409, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 15", "type": "ellipse", "l": 10, "h": 10, "couleur": "#67e0e6", "pos": [977.8, 540.7], "debut": 1.7533656274493554, "fin": 3.6388810000208354, "anim": {"position": [[1.753, [977.8, 540.7], "lin"], [2.225, [954.5, 377.7], "lin"], [2.696, [924.3, 261.3], "lin"], [3.168, [926.2, 191.5], "lin"], [3.639, [939.6, 168.2], "lin"]], "opacite": [[1.753, 0, "lin"], [1.903, 90, "lin"], [2.885, 80, "lin"], [3.639, 0, "lin"]], "echelle": [[1.753, 40, "lin"], [2.053, 100, "out"], [3.639, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 16", "type": "ellipse", "l": 12, "h": 12, "couleur": "#2f55a4", "pos": [971.7, 540.7], "debut": 1.8293436113400503, "fin": 3.2996361497473217, "anim": {"position": [[1.829, [971.7, 540.7], "lin"], [2.197, [880.1, 339.5], "lin"], [2.564, [812.3, 195.8], "lin"], [2.932, [769.6, 109.5], "lin"], [3.3, [706.6, 80.8], "lin"]], "opacite": [[1.829, 0, "lin"], [1.979, 90, "lin"], [2.712, 80, "lin"], [3.3, 0, "lin"]], "echelle": [[1.829, 40, "lin"], [2.129, 100, "out"], [3.3, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 17", "type": "ellipse", "l": 11, "h": 11, "couleur": "#0980c2", "pos": [954.8, 540.7], "debut": 2.1238361505968224, "fin": 4.131728240795999, "anim": {"position": [[2.124, [954.8, 540.7], "lin"], [2.626, [995.5, 358.9], "lin"], [3.128, [1068.9, 229.0], "lin"], [3.63, [1137.2, 151.1], "lin"], [4.132, [1172.2, 125.1], "lin"]], "opacite": [[2.124, 0, "lin"], [2.274, 90, "lin"], [3.329, 80, "lin"], [4.132, 0, "lin"]], "echelle": [[2.124, 40, "lin"], [2.424, 100, "out"], [4.132, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 18", "type": "ellipse", "l": 22, "h": 22, "couleur": "#1fa6b8", "pos": [942.7, 540.7], "debut": 1.604847173364849, "fin": 2.9851133751119554, "anim": {"position": [[1.605, [942.7, 540.7], "lin"], [1.95, [1015.9, 302.7], "lin"], [2.295, [1100.5, 132.7], "lin"], [2.64, [1154.5, 30.8], "lin"], [2.985, [1192.8, -3.2], "lin"]], "opacite": [[1.605, 0, "lin"], [1.755, 90, "lin"], [2.433, 80, "lin"], [2.985, 0, "lin"]], "echelle": [[1.605, 40, "lin"], [1.905, 100, "out"], [2.985, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 19", "type": "ellipse", "l": 12, "h": 12, "couleur": "#67e0e6", "pos": [946.5, 540.7], "debut": 1.4419190317197703, "fin": 3.005083372157954, "anim": {"position": [[1.442, [946.5, 540.7], "lin"], [1.833, [928.6, 281.0], "lin"], [2.224, [890.5, 95.5], "lin"], [2.614, [824.3, -15.8], "lin"], [3.005, [774.5, -52.9], "lin"]], "opacite": [[1.442, 0, "lin"], [1.592, 90, "lin"], [2.38, 80, "lin"], [3.005, 0, "lin"]], "echelle": [[1.442, 40, "lin"], [1.742, 100, "out"], [3.005, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 20", "type": "ellipse", "l": 16, "h": 16, "couleur": "#2f55a4", "pos": [962.7, 540.7], "debut": 1.3160586980495816, "fin": 2.810469034159632, "anim": {"position": [[1.316, [962.7, 540.7], "lin"], [1.69, [906.2, 312.5], "lin"], [2.063, [816.4, 149.6], "lin"], [2.437, [726.8, 51.8], "lin"], [2.81, [670.7, 19.2], "lin"]], "opacite": [[1.316, 0, "lin"], [1.466, 90, "lin"], [2.213, 80, "lin"], [2.81, 0, "lin"]], "echelle": [[1.316, 40, "lin"], [1.616, 100, "out"], [2.81, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 21", "type": "ellipse", "l": 16, "h": 16, "couleur": "#0980c2", "pos": [976.4, 540.7], "debut": 1.5845336419339053, "fin": 3.2715132685439414, "anim": {"position": [[1.585, [976.4, 540.7], "lin"], [2.006, [960.3, 250.0], "lin"], [2.428, [928.4, 42.3], "lin"], [2.85, [924.8, -82.3], "lin"], [3.272, [941.0, -123.8], "lin"]], "opacite": [[1.585, 0, "lin"], [1.735, 90, "lin"], [2.597, 80, "lin"], [3.272, 0, "lin"]], "echelle": [[1.585, 40, "lin"], [1.885, 100, "out"], [3.272, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 22", "type": "ellipse", "l": 19, "h": 19, "couleur": "#1fa6b8", "pos": [975.1, 540.7], "debut": 2.029873101022, "fin": 4.056612085775987, "anim": {"position": [[2.03, [975.1, 540.7], "lin"], [2.537, [895.7, 383.8], "lin"], [3.043, [832.7, 271.7], "lin"], [3.55, [799.9, 204.5], "lin"], [4.057, [755.2, 182.1], "lin"]], "opacite": [[2.03, 0, "lin"], [2.18, 90, "lin"], [3.246, 80, "lin"], [4.057, 0, "lin"]], "echelle": [[2.03, 40, "lin"], [2.33, 100, "out"], [4.057, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 23", "type": "ellipse", "l": 11, "h": 11, "couleur": "#67e0e6", "pos": [959.8, 540.7], "debut": 1.4745487097863246, "fin": 3.5268726266622306, "anim": {"position": [[1.475, [959.8, 540.7], "lin"], [1.988, [887.7, 286.5], "lin"], [2.501, [849.0, 104.9], "lin"], [3.014, [814.8, -4.0], "lin"], [3.527, [747.7, -40.3], "lin"]], "opacite": [[1.475, 0, "lin"], [1.625, 90, "lin"], [2.706, 80, "lin"], [3.527, 0, "lin"]], "echelle": [[1.475, 40, "lin"], [1.775, 100, "out"], [3.527, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 24", "type": "ellipse", "l": 9, "h": 9, "couleur": "#2f55a4", "pos": [944.8, 540.7], "debut": 2.105122267077781, "fin": 3.742288046235667, "anim": {"position": [[2.105, [944.8, 540.7], "lin"], [2.514, [1016.2, 309.1], "lin"], [2.924, [1107.5, 143.7], "lin"], [3.333, [1173.2, 44.4], "lin"], [3.742, [1215.6, 11.3], "lin"]], "opacite": [[2.105, 0, "lin"], [2.255, 90, "lin"], [3.087, 80, "lin"], [3.742, 0, "lin"]], "echelle": [[2.105, 40, "lin"], [2.405, 100, "out"], [3.742, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 25", "type": "ellipse", "l": 12, "h": 12, "couleur": "#0980c2", "pos": [943.7, 540.7], "debut": 1.2848268245244043, "fin": 3.148490392971106, "anim": {"position": [[1.285, [943.7, 540.7], "lin"], [1.751, [1047.4, 369.8], "lin"], [2.217, [1139.2, 247.7], "lin"], [2.683, [1198.9, 174.5], "lin"], [3.148, [1266.1, 150.1], "lin"]], "opacite": [[1.285, 0, "lin"], [1.435, 90, "lin"], [2.403, 80, "lin"], [3.148, 0, "lin"]], "echelle": [[1.285, 40, "lin"], [1.585, 100, "out"], [3.148, 50, "lin"]]}, "opacite": 100}, {"nom": "Particule 26", "type": "ellipse", "l": 18, "h": 18, "couleur": "#1fa6b8", "pos": [957.6, 540.7], "debut": 1.9913460620816175, "fin": 3.4316927794943046, "anim": {"position": [[1.991, [957.6, 540.7], "lin"], [2.351, [994.5, 360.6], "lin"], [2.712, [998.6, 232.0], "lin"], [3.072, [993.7, 154.9], "lin"], [3.432, [1020.3, 129.2], "lin"]], "opacite": [[1.991, 0, "lin"], [2.141, 90, "lin"], [2.856, 80, "lin"], [3.432, 0, "lin"]], "echelle": [[1.991, 40, "lin"], [2.291, 100, "out"], [3.432, 50, "lin"]]}, "opacite": 100}, {"nom": "Signature", "type": "texte", "texte": "PLATEFORME DE FORMATION EN LIGNE", "taille": 34, "police": "Poppins-SemiBold", "poids": "sb", "couleur": "#0b2a55", "align": "centre", "interlettre": 7, "l": 844.8, "pos": [960, 780.2582242660063], "debut": 2.9, "anim": {"position": [[2.9, [960, 810.2582242660063], "lin"], [3.5, [960, 780.2582242660063], "out"]], "opacite": [[2.9, 0, "lin"], [3.3, 100, "lin"]], "flou": [[2.9, 10, "lin"], [3.4, 0, "out"]]}, "fin": 6.0, "opacite": 100}, {"nom": "Trait signature 1", "type": "rect", "l": 120, "h": 4, "rayon": 2, "couleur": "#0980c2", "ancre": "gauche", "pos": [497.5859375, 768.2582242660063], "debut": 3.1, "anim": {"echelle": [[3.1, [0, 100], "lin"], [3.7, [-100, 100], "out"]]}, "fin": 6.0, "opacite": 100}, {"nom": "Trait signature 2", "type": "rect", "l": 120, "h": 4, "rayon": 2, "couleur": "#0980c2", "ancre": "gauche", "pos": [1422.4140625, 768.2582242660063], "debut": 3.1, "anim": {"echelle": [[3.1, [0, 100], "lin"], [3.7, [100, 100], "out"]]}, "fin": 6.0, "opacite": 100}]}], "transitions": [], "audio": [{"nom": "Son du logo", "src": "audio/son_logo.wav"}]};

    var dossier = File($.fileName).parent;

    function couleur(hex) {
        hex = hex.replace('#', '');
        return [
            parseInt(hex.substr(0, 2), 16) / 255,
            parseInt(hex.substr(2, 2), 16) / 255,
            parseInt(hex.substr(4, 2), 16) / 255
        ];
    }

    var cacheMedias = {};

    function importer(chemin, dossierProjet) {
        if (cacheMedias[chemin]) {
            return cacheMedias[chemin];
        }
        var fichier = new File(dossier.fsName + '/' + chemin);
        if (!fichier.exists) {
            alert('Fichier introuvable : ' + fichier.fsName);
            return null;
        }
        var element = app.project.importFile(new ImportOptions(fichier));
        element.parentFolder = dossierProjet;
        cacheMedias[chemin] = element;
        return element;
    }

    function transformation(calque) {
        return calque.property('ADBE Transform Group');
    }

    function definirEase(prop, cles, estSpatiale) {
        var dimensions = 1;
        if (!estSpatiale) {
            var v = prop.keyValue(1);
            dimensions = (v instanceof Array) ? v.length : 1;
        }

        function ease(influence) {
            var tableau = [];
            for (var d = 0; d < dimensions; d++) {
                tableau.push(new KeyframeEase(0, influence));
            }
            return tableau;
        }

        for (var i = 1; i <= prop.numKeys; i++) {
            var typeEntree = cles[i - 1][2] || 'lin';
            var typeSortie = (i < prop.numKeys) ? (cles[i][2] || 'lin') : 'lin';
            var interpEntree = (typeEntree === 'lin') ? KeyframeInterpolationType.LINEAR : KeyframeInterpolationType.BEZIER;
            var interpSortie = (typeSortie === 'io') ? KeyframeInterpolationType.BEZIER : KeyframeInterpolationType.LINEAR;
            try {
                var influenceEntree = (typeEntree === 'io') ? 60 : 85;
                var influenceSortie = (typeSortie === 'io') ? 60 : 0.1;
                if (interpEntree === KeyframeInterpolationType.BEZIER || interpSortie === KeyframeInterpolationType.BEZIER) {
                    prop.setTemporalEaseAtKey(i, ease(influenceEntree), ease(influenceSortie));
                }
                prop.setInterpolationTypeAtKey(i, interpEntree, interpSortie);
            } catch (e) {}
            if (estSpatiale) {
                try {
                    prop.setSpatialAutoBezierAtKey(i, false);
                    prop.setSpatialContinuousAtKey(i, false);
                    var zero = (prop.keyValue(i).length === 3) ? [0, 0, 0] : [0, 0];
                    prop.setSpatialTangentsAtKey(i, zero, zero);
                } catch (e2) {}
            }
        }
    }

    function convertir(nomPropriete, valeur, calqueData) {
        if (nomPropriete === 'position') {
            return calqueData.troisd ? [valeur[0], valeur[1], 0] : [valeur[0], valeur[1]];
        }
        if (nomPropriete === 'echelle') {
            var base = calqueData.echelle_base || 100;
            var sx = (valeur instanceof Array) ? valeur[0] : valeur;
            var sy = (valeur instanceof Array) ? valeur[1] : valeur;
            var r = [sx * base / 100, sy * base / 100];
            if (calqueData.troisd) {
                r.push(base);
            }
            return r;
        }
        return valeur;
    }

    function proprieteAE(calque, nomPropriete) {
        var t = transformation(calque);
        if (nomPropriete === 'position') return t.property('ADBE Position');
        if (nomPropriete === 'echelle') return t.property('ADBE Scale');
        if (nomPropriete === 'rotation') return t.property('ADBE Rotate Z');
        if (nomPropriete === 'rotY') return t.property('ADBE Rotate Y');
        if (nomPropriete === 'opacite') return t.property('ADBE Opacity');
        if (nomPropriete === 'flou') {
            var effet = calque.property('ADBE Effect Parade').addProperty('ADBE Gaussian Blur 2');
            effet.name = 'Flou';
            try {
                effet.property('ADBE Gaussian Blur 2-0003').setValue(1);
            } catch (e) {}
            return effet.property('ADBE Gaussian Blur 2-0001');
        }
        return null;
    }

    function animer(calque, calqueData) {
        var anim = calqueData.anim || {};
        var statiques = {
            position: calqueData.pos,
            echelle: 100,
            rotation: 0,
            opacite: (calqueData.opacite === undefined) ? 100 : calqueData.opacite
        };

        for (var nom in statiques) {
            if (!anim[nom]) {
                proprieteAE(calque, nom).setValue(convertir(nom, statiques[nom], calqueData));
            }
        }

        for (var cle in anim) {
            var cles = anim[cle];
            if (!cles || !cles.length) continue;
            if (cle === 'rotY' && !calqueData.troisd) continue;
            var prop = proprieteAE(calque, cle);
            if (!prop) continue;
            for (var i = 0; i < cles.length; i++) {
                prop.setValueAtTime(cles[i][0], convertir(cle, cles[i][1], calqueData));
            }
            definirEase(prop, cles, cle === 'position');
        }
    }

    function ajouterOmbre(calque) {
        try {
            var ombre = calque.property('ADBE Effect Parade').addProperty('ADBE Drop Shadow');
            ombre.property('ADBE Drop Shadow-0001').setValue([0.02, 0.08, 0.18, 1]);
            var opacite = ombre.property('ADBE Drop Shadow-0002');
            opacite.setValue((opacite.hasMax && opacite.maxValue > 100) ? 90 : 35);
            ombre.property('ADBE Drop Shadow-0003').setValue(180);
            ombre.property('ADBE Drop Shadow-0004').setValue(30);
            ombre.property('ADBE Drop Shadow-0005').setValue(60);
        } catch (e) {}
    }

    function creerForme(comp, calqueData) {
        var calque = comp.layers.addShape();
        var groupe = calque.property('ADBE Root Vectors Group').addProperty('ADBE Vector Group');
        groupe.name = calqueData.type === 'ellipse' ? 'Ellipse' : 'Rectangle';
        var contenu = groupe.property('ADBE Vectors Group');
        var forme;
        if (calqueData.type === 'ellipse') {
            forme = contenu.addProperty('ADBE Vector Shape - Ellipse');
            forme.property('ADBE Vector Ellipse Size').setValue([calqueData.l, calqueData.h]);
        } else {
            forme = contenu.addProperty('ADBE Vector Shape - Rect');
            forme.property('ADBE Vector Rect Size').setValue([calqueData.l, calqueData.h]);
            forme.property('ADBE Vector Rect Roundness').setValue(calqueData.rayon || 0);
            if (calqueData.ancre === 'gauche') {
                forme.property('ADBE Vector Rect Position').setValue([calqueData.l / 2, 0]);
            }
        }
        var remplissage = contenu.addProperty('ADBE Vector Graphic - Fill');
        remplissage.property('ADBE Vector Fill Color').setValue(couleur(calqueData.couleur));
        transformation(calque).property('ADBE Anchor Point').setValue([0, 0]);
        return calque;
    }

    function creerTexte(comp, calqueData) {
        var calque = comp.layers.addText(calqueData.texte);
        var source = calque.property('ADBE Text Properties').property('ADBE Text Document');
        var doc = source.value;
        doc.resetCharStyle();
        doc.resetParagraphStyle();
        doc.fontSize = calqueData.taille;
        try {
            doc.font = calqueData.police;
        } catch (e) {}
        doc.applyFill = true;
        doc.fillColor = couleur(calqueData.couleur);
        doc.applyStroke = false;
        doc.tracking = Math.round((calqueData.interlettre || 0) * 1000 / calqueData.taille);
        doc.justification = (calqueData.align === 'gauche') ? ParagraphJustification.LEFT_JUSTIFY : ParagraphJustification.CENTER_JUSTIFY;
        source.setValue(doc);
        return calque;
    }

    function construireCalques(comp, calques, dossierImages) {
        var parNom = {};
        for (var i = 0; i < calques.length; i++) {
            var c = calques[i];
            var calque;
            if (c.type === 'image') {
                var media = importer(c.src, dossierImages);
                if (!media) continue;
                calque = comp.layers.add(media);
                if (c.pivot) {
                    var base = (c.echelle_base || 100) / 100;
                    transformation(calque).property('ADBE Anchor Point').setValue([c.pivot[0] / base, c.pivot[1] / base]);
                }
            } else if (c.type === 'texte') {
                calque = creerTexte(comp, c);
            } else {
                calque = creerForme(comp, c);
            }
            calque.name = c.nom;
            if (c.troisd) {
                calque.threeDLayer = true;
            }
            if (c.parent && parNom[c.parent]) {
                calque.parent = parNom[c.parent];
            }
            animer(calque, c);
            if (c.ombre) {
                ajouterOmbre(calque);
            }
            calque.motionBlur = true;
            calque.inPoint = Math.max(0, c.debut);
            calque.outPoint = Math.min(comp.duration, Math.max(c.fin, c.debut + 1 / DATA.ips));
            parNom[c.nom] = calque;
        }
    }

    app.beginUndoGroup('Construire l\'animation du logo TOUN');

    var projet = app.project;
    var dossierRacine = projet.items.addFolder(DATA.titre);
    var dossierScenes = projet.items.addFolder('Scènes');
    var dossierImages = projet.items.addFolder('Images');
    var dossierAudio = projet.items.addFolder('Audio');
    dossierScenes.parentFolder = dossierRacine;
    dossierImages.parentFolder = dossierRacine;
    dossierAudio.parentFolder = dossierRacine;

    var principale = projet.items.addComp(DATA.titre + ' - Final', DATA.largeur, DATA.hauteur, 1, DATA.duree, DATA.ips);
    principale.parentFolder = dossierRacine;
    principale.motionBlur = true;

    for (var s = 0; s < DATA.scenes.length; s++) {
        var scene = DATA.scenes[s];
        var comp = projet.items.addComp(scene.nom, DATA.largeur, DATA.hauteur, 1, scene.duree, DATA.ips);
        comp.parentFolder = dossierScenes;
        comp.motionBlur = true;
        comp.bgColor = couleur(scene.fond);
        var fond = comp.layers.addSolid(couleur(scene.fond), 'Fond', DATA.largeur, DATA.hauteur, 1, scene.duree);
        fond.locked = false;
        construireCalques(comp, scene.calques, dossierImages);
        var calqueScene = principale.layers.add(comp);
        calqueScene.startTime = scene.debut;
    }

    var transitions = projet.items.addComp('Transitions', DATA.largeur, DATA.hauteur, 1, DATA.duree, DATA.ips);
    transitions.parentFolder = dossierScenes;
    transitions.motionBlur = true;
    construireCalques(transitions, DATA.transitions, dossierImages);
    var calqueTransitions = principale.layers.add(transitions);
    calqueTransitions.startTime = 0;

    for (var a = 0; a < DATA.audio.length; a++) {
        var son = importer(DATA.audio[a].src, dossierAudio);
        if (son) {
            var calqueSon = principale.layers.add(son);
            calqueSon.name = DATA.audio[a].nom;
            calqueSon.startTime = 0;
        }
    }

    principale.openInViewer();
    app.endUndoGroup();

    alert('Animation prête : composition "' + DATA.titre + ' - Final".');
})();
