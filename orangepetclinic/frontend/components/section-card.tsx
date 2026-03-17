type SectionCardProps = {
  title: string;
  description: string;
};

export function SectionCard({ title, description }: SectionCardProps) {
  return (
    <article className="rounded-lg border border-orange-200 bg-white p-5 shadow-sm">
      <h2 className="text-xl font-semibold text-brand-700">{title}</h2>
      <p className="mt-2 text-slate-700">{description}</p>
    </article>
  );
}
