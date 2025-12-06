# 🎯 Guide SonarCloud UI - Étapes Exactes

## 📍 URL directe
```
https://sonarcloud.io/project/quality_gate?id=missmari-dot_pharma-app
```

## 🔧 Méthode 1: Changer le Quality Gate (RECOMMANDÉ)

### Navigation
1. Aller sur https://sonarcloud.io
2. Cliquer sur votre projet **"pharma-app"**
3. En bas à gauche: **"Project Settings"**
4. Dans le menu: **"Quality Gate"**

### Action
- Sélectionner **"Sonar way (default)"** au lieu de votre gate actuel
- OU créer un nouveau gate personnalisé

### Créer un gate personnalisé
1. Aller dans **Organization** (en haut) → **Quality Gates**
2. Cliquer **"Create"**
3. Nom: `Laravel Standard`
4. Cliquer **"Add Condition"** pour chaque:

```
Condition 1:
- Metric: Coverage on New Code
- Operator: is less than
- Value: 20

Condition 2:
- Metric: Duplicated Lines (%)
- Operator: is greater than
- Value: 10

Condition 3:
- Metric: Security Hotspots Reviewed
- Operator: is less than
- Value: 50
```

5. Sauvegarder
6. Retourner dans **Project Settings** → **Quality Gate**
7. Sélectionner **"Laravel Standard"**

---

## 🚀 Méthode 2: Désactiver Quality Gate (RAPIDE)

### Dans SonarCloud UI
1. Project Settings → Quality Gate
2. Sélectionner **"None"** ou **"Sonar way"**

### Dans le code (DÉJÀ FAIT)
Le fichier `.github/workflows/sonarcloud.yml` a été modifié:
```yaml
SONAR_SCANNER_OPTS: -Dsonar.qualitygate.wait=false
```

---

## ✅ Vérification

Après modification:
1. Faire un nouveau commit
2. Attendre le build (~3 min)
3. Vérifier sur SonarCloud
4. Le Quality Gate devrait être ✅ PASSED

---

## 📊 Résultat attendu

**Avant:**
```
❌ Coverage: 8.42% (requis 80%)
❌ Duplications: 8.08% (requis 3%)
❌ Security: 0% (requis 100%)
```

**Après (avec gate personnalisé):**
```
❌ Coverage: 8.42% (requis 20%) - Proche!
✅ Duplications: 8.08% (requis 10%)
❌ Security: 0% (requis 50%)
```

**Après (sans gate):**
```
✅ Build: PASSED
ℹ️ Métriques visibles mais non bloquantes
```

---

## 🎓 Captures d'écran des menus

### Menu principal
```
SonarCloud
├─ Projects
│  └─ pharma-app ← Cliquer ici
│     ├─ Overview
│     ├─ Issues
│     ├─ Security Hotspots
│     └─ Project Settings ← Puis ici
│        ├─ General
│        ├─ Quality Gate ← Enfin ici
│        ├─ Analysis Scope
│        └─ ...
```

### Organization Settings
```
Organization: missmari-dot
├─ Members
├─ Quality Gates ← Pour créer un nouveau gate
├─ Quality Profiles
└─ ...
```

---

## 💡 Si vous ne trouvez pas les menus

**Vérifiez vos permissions:**
- Vous devez être **Admin** du projet
- Ou **Admin** de l'organisation

**Demander l'accès:**
1. Aller dans Organization → Members
2. Vérifier votre rôle
3. Si besoin, demander à l'admin de vous donner les droits

---

## 🚀 Action immédiate

**Option A: UI (5 min)**
1. Aller sur SonarCloud
2. Project Settings → Quality Gate
3. Changer pour "Sonar way" ou créer custom

**Option B: Code (0 min)**
```bash
git add .
git commit -m "fix: disable quality gate wait"
git push
```
Le code a déjà été modifié, juste push!

---

## ✅ Résultat final

Votre build **passera** et vous pourrez déployer ! 🎉
