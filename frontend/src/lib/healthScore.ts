export function getHealthScoreLabel(score: number): string {
  if (score >= 80) {
    return "Gut";
  }

  if (score >= 60) {
    return "Okay";
  }

  if (score >= 40) {
    return "Schwach";
  }

  return "Kritisch";
}