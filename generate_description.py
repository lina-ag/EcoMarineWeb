import sys
import json
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import numpy as np

# ── ARGUMENTS ────────────────────────────────────────────────────────────────
if len(sys.argv) < 3:
    print(json.dumps({"error": "Arguments manquants: nom_activite capacite"}))
    sys.exit(1)

nom_activite = sys.argv[1].strip().lower()
capacite     = sys.argv[2].strip()
mois         = sys.argv[3].strip() if len(sys.argv) > 3 else ""

# ── DATASET ───────────────────────────────────────────────────────────────────
DATASET = {
    "kayak": [
        "Partez à la découverte des eaux cristallines de Kuriat Island à bord de votre kayak. Une expérience unique au cœur de la réserve marine, accessible à tous les niveaux.",
        "Explorez les criques et les côtes sauvages de Monastir en kayak. Idéal pour {capacite}, cette activité allie sport, nature et découverte marine.",
        "Une aventure en kayak au large de Kuriat Island vous attend. Pagayez en harmonie avec la mer Méditerranée et découvrez une faune marine exceptionnelle.",
        "Glissez sur les eaux turquoise de la réserve naturelle de Kuriat en kayak. Une activité écologique et sportive pour {capacite} passionnés de nature.",
    ],
    "plongée": [
        "Plongez dans les profondeurs de la mer Méditerranée et explorez les fonds marins de Kuriat Island. Une expérience inoubliable pour découvrir la richesse de la faune et de la flore sous-marine.",
        "Découvrez les merveilles sous-marines de la réserve marine de Kuriat lors de cette session de plongée pour {capacite}, encadrés par des moniteurs expérimentés.",
        "Une immersion totale dans l'écosystème marin de Kuriat Island. Observez les poissons, les coraux et les herbiers de posidonie lors de cette plongée guidée.",
        "La plongée à Kuriat Island vous ouvre les portes d'un monde sous-marin préservé. Idéal pour {capacite} souhaitant découvrir la biodiversité méditerranéenne.",
    ],
    "snorkeling": [
        "Explorez la surface des eaux de Kuriat Island avec masque et tuba. Observez les tortues marines, les poissons colorés et les herbiers de posidonie depuis la surface.",
        "Le snorkeling à Kuriat Island est une activité accessible à tous. Pour {capacite}, une découverte magique de la faune marine méditerranéenne.",
        "Enfilez masque et palmes et partez à la rencontre des habitants des fonds marins de Kuriat. Une activité familiale et écologique pour {capacite}.",
        "Une session de snorkeling dans les eaux protégées de la réserve de Kuriat Island. Observez la richesse naturelle de la mer Méditerranée depuis la surface.",
    ],
    "paddle": [
        "Pratiquez le stand-up paddle dans le cadre exceptionnel de la réserve marine de Kuriat Island. Équilibre, sport et nature pour {capacite}.",
        "Le paddle boarding à Kuriat vous offre une perspective unique sur la mer et l'île. Une activité douce et contemplative pour {capacite} de tous niveaux.",
        "Montez sur votre planche et pagayez dans les eaux calmes de Kuriat Island. Une expérience sportive et relaxante au cœur de la Méditerranée.",
        "Découvrez Kuriat Island depuis votre planche de paddle. Sport, équilibre et communion avec la nature pour {capacite} enthousiastes.",
    ],
    "surf": [
        "Surfez sur les vagues de Kuriat Island et explorez les côtes dynamiques de la réserve marine. Une activité sportive et exaltante pour {capacite} amateurs de surf.",
        "Le surf à Kuriat Island offre des conditions exceptionnelles pour tous les niveaux. Pour {capacite}, une expérience de glisse unique face aux vagues méditerranéennes.",
        "Prenez les meilleures vagues de Kuriat Island lors de cette session de surf guidée. Une activité dynamique et sportive pour {capacite} passionnés d'action.",
        "Découvrez le surf à Kuriat Island dans le cadre préservé de la réserve naturelle. Pour {capacite}, une communion entre sport, nature et mer Méditerranée.",
    ],
    "observation": [
        "Participez à une session d'observation des tortues marines de Kuriat Island. Une expérience unique dans la plus grande nurserie de tortues caouannes de Méditerranée.",
        "Observez les tortues marines dans leur habitat naturel à Kuriat Island. Pour {capacite}, une rencontre inoubliable avec ces reptiles marins protégés.",
        "Une sortie d'observation de la faune marine de Kuriat Island. Tortues, dauphins et oiseaux marins vous attendent pour {capacite} sensibles à l'écologie.",
        "Découvrez la biodiversité exceptionnelle de la réserve marine de Kuriat lors de cette session d'observation guidée pour {capacite}.",
    ],
    "nettoyage": [
        "Participez au nettoyage des fonds marins et des plages de Kuriat Island. Un acte écologique fort pour {capacite} engagés pour la protection des océans.",
        "Rejoignez notre équipe de bénévoles pour nettoyer les plages et les côtes de Kuriat Island. Une action solidaire et écologique pour {capacite}.",
        "Un nettoyage des plages de Kuriat Island organisé pour préserver la beauté naturelle de la réserve marine. Pour {capacite} soucieux de l'environnement.",
        "Agissez concrètement pour la protection de la réserve marine de Kuriat en participant à ce nettoyage collectif. Activité ouverte à {capacite}.",
    ],
    "camping": [
        "Vivez une nuit unique sous les étoiles à Kuriat Island. Le camping écologique vous reconnecte avec la nature dans un cadre préservé pour {capacite}.",
        "Une expérience de camping authentique au cœur de la réserve naturelle de Kuriat Island. Pour {capacite} aventuriers en quête de nature et de calme.",
        "Dormez à la belle étoile sur l'île de Kuriat et réveillez-vous au son des vagues. Un camping écologique pour {capacite} amoureux de la nature.",
        "Le camping à Kuriat Island est une immersion totale dans la nature méditerranéenne. Pour {capacite} souhaitant vivre une expérience authentique.",
    ],
    "yoga": [
        "Pratiquez le yoga face à la mer Méditerranée à Kuriat Island. Une session de bien-être et de sérénité pour {capacite} au coucher du soleil.",
        "Le yoga en bord de mer à Kuriat Island vous offre une expérience de relaxation unique. Pour {capacite}, un moment de paix et de connexion avec la nature.",
        "Une session de yoga au cœur de la réserve naturelle de Kuriat. Respirez l'air marin et retrouvez l'équilibre pour {capacite}.",
        "Alignez corps et esprit lors de cette séance de yoga face aux eaux turquoise de Kuriat Island. Pour {capacite} en quête de bien-être.",
    ],
    "analyse": [
        "Participez à une session de recherche et d'analyse de la qualité de l'eau de la réserve marine de Kuriat. Pour {capacite} passionnés de sciences marines.",
        "Une activité scientifique dédiée à l'analyse de l'écosystème marin de Kuriat Island. Pour {capacite} chercheurs et passionnés de biologie marine.",
        "Contribuez à la recherche marine en participant à l'analyse des données environnementales de Kuriat Island. Pour {capacite} participants engagés.",
        "Session d'analyse et de collecte de données marines à Kuriat Island. Une activité scientifique rigoureuse pour {capacite}.",
    ],
    "plantation": [
        "Participez à la plantation de végétaux marins et côtiers pour restaurer l'écosystème de Kuriat Island. Une action écologique pour {capacite}.",
        "Contribuez à la reforestation côtière de Kuriat Island lors de cette session de plantation. Pour {capacite} engagés pour l'environnement.",
        "Plantez des espèces végétales locales pour préserver la biodiversité de Kuriat Island. Une activité écologique et solidaire pour {capacite}.",
        "Une journée de plantation pour restaurer la végétation côtière de la réserve de Kuriat. Pour {capacite} passionnés de nature.",
    ],
    "tortue": [
        "Partez à la rencontre des tortues caouannes de Kuriat Island, la plus grande nurserie de tortues marines de Méditerranée. Une expérience protégée pour {capacite}.",
        "Observez et contribuez à la protection des tortues marines de Kuriat Island. Pour {capacite} participants sensibles à la conservation des espèces marines.",
        "Une sortie de sauvetage et d'observation des tortues marines de Kuriat. Pour {capacite} participants engagés dans la protection de ces espèces emblématiques.",
        "Participez à la protection des tortues caouannes de Kuriat Island. Une activité scientifique et émotionnelle pour {capacite} amoureux de la nature.",
    ],
    "default": [
        "Découvrez la beauté naturelle de Kuriat Island lors de cette activité écologique unique. Une expérience inoubliable pour {capacite} au cœur de la réserve marine de Monastir.",
        "Une activité écotouristique exceptionnelle à Kuriat Island, réserve naturelle protégée de la Méditerranée. Pour {capacite} en quête d'aventure et de nature.",
        "Partez à la découverte de la réserve marine de Kuriat Island lors de cette activité unique. Pour {capacite} souhaitant vivre une expérience authentique en Méditerranée.",
        "Vivez une expérience écologique unique à Kuriat Island, joyau naturel de Monastir. Une activité pour {capacite} respectueux de la nature.",
    ]
}

# ── DÉTECTION DU TYPE VIA TF-IDF ──────────────────────────────────────────────
corpus    = []
type_keys = []

for key, templates in DATASET.items():
    corpus.append(" ".join(templates) + " " + key)
    type_keys.append(key)

corpus.append(nom_activite)

vectorizer   = TfidfVectorizer(analyzer='char_wb', ngram_range=(2, 4))
tfidf_matrix = vectorizer.fit_transform(corpus)

query_vec    = tfidf_matrix[-1]
type_vecs    = tfidf_matrix[:-1]
similarities = cosine_similarity(query_vec, type_vecs).flatten()

best_idx  = int(np.argmax(similarities))
best_type = type_keys[best_idx]
score     = float(similarities[best_idx])

if score < 0.05:
    best_type = "default"

# ── SÉLECTION DU TEMPLATE ─────────────────────────────────────────────────────
templates = DATASET[best_type]
cap       = int(capacite) if capacite.isdigit() else 10

if cap <= 5:
    template = templates[0]
elif cap <= 15:
    template = templates[1]
elif cap <= 30:
    template = templates[2]
else:
    template = templates[3]

# ── PERSONNALISATION ──────────────────────────────────────────────────────────
description = template.replace("{capacite}", capacite + " personnes")

mois_map = {
    "1": "en hiver", "2": "en hiver", "3": "au printemps",
    "4": "au printemps", "5": "au printemps", "6": "en été",
    "7": "en été", "8": "en été", "9": "en automne",
    "10": "en automne", "11": "en automne", "12": "en hiver"
}
if mois and mois in mois_map:
    description += f" Cette activité se déroule {mois_map[mois]}, une période idéale pour profiter de la réserve marine de Kuriat Island."

# ── RÉSULTAT ──────────────────────────────────────────────────────────────────
print(json.dumps({
    "description": description,
    "type_detecte": best_type,
    "score": round(score, 3)
}, ensure_ascii=False))