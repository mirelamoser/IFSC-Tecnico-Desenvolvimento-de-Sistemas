<?php

declare(strict_types=1);

namespace PhiloQuest;

/**
 * Notas por pergunta da Etapa 2 (formato no campo feedback da submissão).
 */
final class Etapa2AvaliacaoHelper
{
    public const PREFIXO_PERGUNTAS = 'Questionamentos Elaborados:';

    /** @return list<string> */
    public static function extrairPerguntas(string $descricao): array
    {
        $textoLimpo = str_ireplace(self::PREFIXO_PERGUNTAS, '', $descricao);
        $perguntas = [];

        foreach (explode("\n", trim($textoLimpo)) as $linha) {
            $linha = trim($linha);
            if ($linha === '') {
                continue;
            }
            $perguntas[] = preg_replace('/^\d+[\.\-\)]\s*/', '', $linha) ?? $linha;
        }

        return $perguntas;
    }

    /** @return array<int, string> número da pergunta (1-based) => conceito */
    public static function parseNotasPorPergunta(?string $feedback): array
    {
        if ($feedback === null || trim($feedback) === '') {
            return [];
        }

        $notas = [];
        $padrao = '/Pergunta\s+(\d+)\s*:\s*(.+?)(?=\r?\n\s*Pergunta\s+\d+\s*:|\r?\n\s*PARECER\s+GERAL:|$)/is';

        if (preg_match_all($padrao, $feedback, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $notas[(int) $m[1]] = trim($m[2]);
            }
        }

        return $notas;
    }

    /** Bom / Excelente — o professor não reavalia; Refazer / Regular — pode reavaliar após revisão */
    public static function notaDeveSerMantida(?string $nota): bool
    {
        if ($nota === null || trim($nota) === '') {
            return false;
        }

        $n = mb_strtolower($nota);

        if (str_contains($n, 'refazer') || str_contains($n, 'regular')) {
            return false;
        }

        return str_contains($n, 'bom') || str_contains($n, 'excelente');
    }

    /**
     * Garante no servidor que notas já aprovadas (Bom/Excelente) não sejam alteradas no POST.
     *
     * @param array<int|string, string> $notasPost índice 0-based => conceito
     * @param array<int, string> $notasAnteriores número 1-based => conceito
     * @return array<int, string>
     */
    public static function mesclarNotasComAnteriores(array $notasPost, array $notasAnteriores): array
    {
        $resultado = $notasPost;

        foreach ($notasAnteriores as $numPergunta => $notaAntiga) {
            if (!self::notaDeveSerMantida($notaAntiga)) {
                continue;
            }
            $resultado[(int) $numPergunta - 1] = $notaAntiga;
        }

        ksort($resultado, SORT_NUMERIC);

        return $resultado;
    }

    /**
     * @param array<int|string, string> $notasPorIndice índice 0-based
     */
    public static function montarFeedback(array $notasPorIndice, string $parecerGeral): string
    {
        $feedback = '';

        if ($notasPorIndice !== []) {
            $feedback .= "AVALIAÇÃO:\n";
            ksort($notasPorIndice, SORT_NUMERIC);
            foreach ($notasPorIndice as $indice => $nota) {
                $num = (int) $indice + 1;
                $feedback .= "Pergunta {$num}: {$nota}\n";
            }
            $feedback .= "\nPARECER GERAL:\n";
        }

        return $feedback . trim($parecerGeral);
    }
}
