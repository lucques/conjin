package res;

public class Akzeptor {
    public static void main(String[] args) {
        System.out.println("Ergebnis für 'aabb': " + testWortFuerL3("aabb"));
        System.out.println("Ergebnis für 'aabbb': " + testWortFuerL3("aabbb"));
    }

    static boolean testWortFuerL3(String wort)
    {
        // Die Anzahl der Zeichen muss gerade sein.
        if (wort.length() % 2 == 1)
        {
            return false; // Verwerfen
        }

        // Anschliessend von links und von rechts auf `a` und `b` pruefen
        for (int i = 0; i < wort.length() / 2; i++)
        {
            if (wort.charAt(i) != 'a' ||
                wort.charAt(wort.length() - i - 1) != 'b')
            {
                return false; // Verwerfen
            }
        }

        // Wenn vorher nicht vereits verworfen wurde, akzeptiere nun.
        return true;
    }
}