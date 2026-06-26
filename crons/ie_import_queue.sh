#!/bin/bash
# --- Buytini IE Import Queue ---
# Послідовний запуск імпортів OpenCart IE через CLI
# Продовжує виконання навіть при помилках
# Між імпортами робить паузу (щоб знизити навантаження)

set -o pipefail

# Встановити PATH для cron
export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

# Перейти в робочу директорію
cd /var/www/www-root/data/www/buytini.com || exit 1

PHP_PATH="/opt/php74/bin/php"
SCRIPT_PATH="/var/www/www-root/data/www/buytini.com/admin/model/extension/module/ie_cron_jobs.php"
LOG_DIR="/var/www/www-root/data/www/buytini.com/system/storage/logs"
LOG_FILE="$LOG_DIR/ie_queue_$(date '+%Y-%m-%d_%H-%M-%S').log"

# --- Налаштування ---
PROFILES=(1 2 3 4 5 6 7 8 9 10 11 12 13)       # ID профілів імпорту
PAUSE_BETWEEN=10       # Пауза між імпортами в секундах
LOG_RETENTION_DAYS=30  # Зберігати логи N днів

# --- Перевірки ---
mkdir -p "$LOG_DIR"

if [ ! -f "$PHP_PATH" ]; then
    echo "❌ КРИТИЧНА ПОМИЛКА: PHP не знайдено: $PHP_PATH" | tee -a "$LOG_FILE"
    exit 1
fi

if [ ! -f "$SCRIPT_PATH" ]; then
    echo "❌ КРИТИЧНА ПОМИЛКА: Скрипт не знайдено: $SCRIPT_PATH" | tee -a "$LOG_FILE"
    exit 1
fi

# Очистка старих логів черги (не чіпаємо логи PHP-скрипта!)
find "$LOG_DIR" -name "ie_queue_*.log" -mtime +$LOG_RETENTION_DAYS -delete 2>/dev/null

# --- Основний цикл ---
echo "=== START IE IMPORT QUEUE $(date '+%Y-%m-%d %H:%M:%S') ===" | tee -a "$LOG_FILE"
echo "Профілі для імпорту: ${PROFILES[*]}" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"

TOTAL_PROFILES=${#PROFILES[@]}
SUCCESSFUL=0
FAILED=0

for i in "${!PROFILES[@]}"; do
    ID="${PROFILES[$i]}"
    PROFILE_NUM=$((i + 1))

    echo "--- [$PROFILE_NUM/$TOTAL_PROFILES] Запуск профілю ID=$ID ---" | tee -a "$LOG_FILE"

    START_TIME=$(date +%s)
    # Зберігаємо вивід кожного профілю окремо для перевірки
    PROFILE_LOG="${LOG_FILE%.log}_profile_$ID.log"
    $PHP_PATH $SCRIPT_PATH action=cron_start profile_id=$ID 2>&1 | tee -a "$PROFILE_LOG"
    RESULT=$?
    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))

    # Перевірити реальний результат імпорту
    if grep -q "Імпорт успішно завершений" "$PROFILE_LOG" 2>/dev/null; then
        echo "✅ Профіль ID=$ID завершено успішно (тривалість ${DURATION}s)" | tee -a "$LOG_FILE"
        ((SUCCESSFUL++))

        # Якщо була помилка SMTP - попередити, але не вважати за фатальну
        if grep -q "EHLO not accepted" "$PROFILE_LOG" 2>/dev/null; then
            echo "⚠️  Увага: Помилка відправки email-звіту (SMTP)" | tee -a "$LOG_FILE"
        fi
    elif [ $RESULT -ne 0 ]; then
        echo "❌ Помилка при виконанні профілю ID=$ID (код $RESULT, тривалість ${DURATION}s)" | tee -a "$LOG_FILE"
        ((FAILED++))
    else
        echo "✅ Профіль ID=$ID завершено (тривалість ${DURATION}s)" | tee -a "$LOG_FILE"
        ((SUCCESSFUL++))
    fi

    # Пауза між імпортами (крім останнього)
    if [ $i -lt $((TOTAL_PROFILES - 1)) ]; then
        echo "⏸  Очікування ${PAUSE_BETWEEN}s перед наступним імпортом..." | tee -a "$LOG_FILE"
        sleep $PAUSE_BETWEEN
    fi

    echo "" | tee -a "$LOG_FILE"
done

# --- Підсумок ---
echo "=== FINISHED ALL IMPORTS $(date '+%Y-%m-%d %H:%M:%S') ===" | tee -a "$LOG_FILE"
echo "📊 Результати: Успішно=$SUCCESSFUL | Помилок=$FAILED | Всього=$TOTAL_PROFILES" | tee -a "$LOG_FILE"
echo "📄 Головний лог: $LOG_FILE" | tee -a "$LOG_FILE"

# Відправити email при помилках (опціонально)
if [ $FAILED -gt 0 ] && command -v mail >/dev/null 2>&1; then
    echo "Імпорт завершився з помилками. Деталі в логах: $LOG_FILE" | \
        mail -s "⚠️ Buytini Import: $FAILED помилок" info@buytini.com
fi

# Повернути код помилки, якщо були невдачі
[ $FAILED -eq 0 ] && exit 0 || exit 1