# 📱 Budowanie APK - Przewodnik

Ten dokument opisuje jak zbudować aplikację Android (APK) z MyDream Video App.

## ✅ Co jest już zainstalowane

- ✅ Node.js v22.21.1
- ✅ npm v10.9.4
- ✅ Cordova v12.0.0
- ✅ Java OpenJDK 21
- ✅ Gradle

## 📋 Co brakuje (opcjonalnie)

- ⚠️ Android SDK - potrzebne do budowania APK na serwerze
- ⚠️ Android Build Tools

---

## 🎯 Opcja A: Budowanie Lokalne (ZALECANE)

Jest to **najlepszy** sposób, szczególnie jeśli nie masz pełnej kontroli nad serwerem.

### Krok 1: Pobierz konfigurację z aplikacji

1. Otwórz aplikację w przeglądarce
2. Przejdź do **Settings** (⚙️)
3. Znajdź sekcję **"📱 Aplikacja Mobilna"**
4. Kliknij **"Pobierz konfigurację Cordova"**
5. Zapisz plik ZIP na swoim komputerze

### Krok 2: Zainstaluj Android Studio

**Windows/Mac/Linux:**
1. Pobierz [Android Studio](https://developer.android.com/studio)
2. Zainstaluj z domyślnymi ustawieniami
3. Uruchom Android Studio
4. Zainstaluj Android SDK (Android Studio zaproponuje to automatycznie)
5. Zainstaluj Android SDK Build-Tools przez SDK Manager

### Krok 3: Zainstaluj Node.js i Cordova (na swoim komputerze)

**Windows:**
```bash
# Pobierz Node.js z https://nodejs.org/
# Następnie:
npm install -g cordova
```

**Mac:**
```bash
brew install node
npm install -g cordova
```

**Linux:**
```bash
sudo apt install nodejs npm
sudo npm install -g cordova
```

### Krok 4: Zbuduj APK

```bash
# Rozpakuj pobrany ZIP
unzip mydream-cordova-config.zip
cd mydream-app

# Utwórz projekt Cordova
cordova create . com.mydream.videoapp "MyDream Video"

# Dodaj platformę Android
cordova platform add android

# Zbuduj APK
cordova build android

# APK będzie w:
# platforms/android/app/build/outputs/apk/debug/app-debug.apk
```

### Krok 5: Zainstaluj na telefonie

**Opcja 1: Przez USB**
```bash
# Włącz USB Debugging na telefonie (Settings > Developer Options)
cordova run android
```

**Opcja 2: Ręcznie**
1. Skopiuj `app-debug.apk` na telefon
2. Otwórz plik na telefonie
3. Zezwól na instalację z nieznanych źródeł
4. Zainstaluj

---

## 🎯 Opcja B: Budowanie na Serwerze (Zaawansowane)

**⚠️ UWAGA:** Instalacja Android SDK na serwerze zajmuje ~5GB miejsca i jest skomplikowana.

### Krok 1: Zainstaluj Android Command Line Tools

```bash
# Utwórz katalog dla Android SDK
sudo mkdir -p /opt/android-sdk
cd /opt/android-sdk

# Pobierz Command Line Tools
wget https://dl.google.com/android/repository/commandlinetools-linux-11076708_latest.zip

# Rozpakuj
unzip commandlinetools-linux-11076708_latest.zip
mkdir -p cmdline-tools/latest
mv cmdline-tools/* cmdline-tools/latest/ 2>/dev/null || true

# Dodaj do PATH
export ANDROID_HOME=/opt/android-sdk
export PATH=$PATH:$ANDROID_HOME/cmdline-tools/latest/bin
export PATH=$PATH:$ANDROID_HOME/platform-tools

# Dodaj do ~/.bashrc aby było permanentne
echo 'export ANDROID_HOME=/opt/android-sdk' >> ~/.bashrc
echo 'export PATH=$PATH:$ANDROID_HOME/cmdline-tools/latest/bin' >> ~/.bashrc
echo 'export PATH=$PATH:$ANDROID_HOME/platform-tools' >> ~/.bashrc
```

### Krok 2: Zainstaluj Android SDK i Build Tools

```bash
# Zaakceptuj licencje
yes | sdkmanager --licenses

# Zainstaluj wymagane komponenty
sdkmanager "platform-tools" "platforms;android-34" "build-tools;34.0.0"

# Sprawdź instalację
sdkmanager --list_installed
```

### Krok 3: Ustaw zmienne środowiskowe dla Cordova

```bash
# Dodaj do ~/.bashrc
echo 'export ANDROID_SDK_ROOT=/opt/android-sdk' >> ~/.bashrc
echo 'export GRADLE_USER_HOME=/opt/gradle' >> ~/.bashrc

# Przeładuj
source ~/.bashrc
```

### Krok 4: Zbuduj APK przez UI

1. Otwórz aplikację w przeglądarce
2. Przejdź do **Settings**
3. W sekcji **"📱 Aplikacja Mobilna"**
4. Wypełnij formularz:
   - **Nazwa aplikacji:** MyDream Video
   - **Package ID:** com.mydream.videoapp
   - **Wersja:** 1.0.0
5. Kliknij **"Zbuduj APK"**
6. Poczekaj (może to zająć kilka minut)
7. Pobierz gotowy APK

---

## 🔧 Rozwiązywanie Problemów

### Cordova mówi "niedostępna" w UI

**Rozwiązanie:**
```bash
# Sprawdź czy Cordova jest zainstalowana
cordova --version

# Jeśli nie działa, zainstaluj ponownie
npm install -g cordova

# Sprawdź czy PHP widzi cordovę
php -r "exec('cordova --version 2>&1', \$o, \$c); echo 'Return code: ' . \$c . PHP_EOL;"
```

### Android SDK not found

**Rozwiązanie:**
```bash
# Sprawdź zmienne środowiskowe
echo $ANDROID_HOME
echo $ANDROID_SDK_ROOT

# Jeśli puste, ustaw je
export ANDROID_HOME=/opt/android-sdk
export ANDROID_SDK_ROOT=/opt/android-sdk

# I dodaj do ~/.bashrc
```

### Gradle build failed

**Rozwiązanie:**
```bash
# Wyczyść cache Gradle
rm -rf ~/.gradle/caches
rm -rf build/

# Spróbuj ponownie
cordova build android --verbose
```

### "Java version not compatible"

**Rozwiązanie:**
```bash
# Sprawdź wersję Java
java -version

# Cordova 12 wymaga Java 11 lub 17
# Masz Java 21, co może powodować problemy

# Zainstaluj Java 17
sudo apt install openjdk-17-jdk

# Ustaw Java 17 jako domyślną
sudo update-alternatives --config java
```

---

## 📊 Porównanie Opcji

| | Lokalnie | Na Serwerze |
|---|---|---|
| **Trudność** | ⭐⭐ Łatwe | ⭐⭐⭐⭐⭐ Bardzo trudne |
| **Miejsce na dysku** | ~2GB (na komputerze) | ~5GB (na serwerze) |
| **Czas setup** | 15-30 minut | 1-2 godziny |
| **Zalety** | - Łatwa instalacja<br>- Android Studio GUI<br>- Łatwe debugowanie | - Automatyzacja<br>- Budowanie przez UI |
| **Wady** | - Wymaga komputera<br>- Ręczne budowanie | - Skomplikowana instalacja<br>- Dużo miejsca<br>- Problemy z uprawnieniami |
| **Zalecane dla** | Wszyscy użytkownicy | Zaawansowani / Serwery z >10GB |

---

## 💡 Najlepsze Praktyki

### Dla Wydania Produkcyjnego

```bash
# Zamiast debug APK, zbuduj release
cordova build android --release

# Podpisz APK (wymaga keystore)
jarsigner -verbose -sigalg SHA256withRSA -digestalg SHA-256 \
  -keystore my-release-key.keystore \
  app-release-unsigned.apk alias_name

# Zoptymalizuj (zipalign)
zipalign -v 4 app-release-unsigned.apk MyDreamVideo.apk
```

### Konfiguracja config.xml

Przed budowaniem, edytuj `config.xml`:

```xml
<widget id="com.mydream.videoapp" version="1.0.0">
    <name>MyDream Video</name>
    <description>
        Osobista biblioteka wideo
    </description>
    <author email="you@example.com">
        Your Name
    </author>

    <!-- Uprawnienia -->
    <allow-intent href="http://*/*" />
    <allow-intent href="https://*/*" />
    <allow-intent href="tel:*" />
    <allow-intent href="sms:*" />
    <allow-intent href="mailto:*" />

    <!-- Android specyficzne -->
    <platform name="android">
        <preference name="android-minSdkVersion" value="24" />
        <preference name="android-targetSdkVersion" value="34" />
    </platform>
</widget>
```

---

## 📚 Dodatkowe Zasoby

- [Cordova Documentation](https://cordova.apache.org/docs/en/latest/)
- [Android Studio Download](https://developer.android.com/studio)
- [Android SDK Command-line Tools](https://developer.android.com/studio/command-line)
- [Cordova Android Platform Guide](https://cordova.apache.org/docs/en/latest/guide/platforms/android/)

---

## ✅ Podsumowanie

**Dla większości użytkowników:**
1. Pobierz konfigurację z UI aplikacji
2. Zainstaluj Android Studio na swoim komputerze
3. Zbuduj APK lokalnie z `cordova build android`

**Dla zaawansowanych (serwer):**
1. Zainstaluj Android SDK na serwerze (~5GB)
2. Skonfiguruj zmienne środowiskowe
3. Buduj APK przez UI aplikacji

**Status w Twojej aplikacji:**
- ✅ Cordova jest dostępna
- ✅ Możesz pobrać konfigurację
- ⚠️ Bez Android SDK nie zbudujesz na serwerze (ale możesz lokalnie!)

---

**Miłego budowania! 📱**
