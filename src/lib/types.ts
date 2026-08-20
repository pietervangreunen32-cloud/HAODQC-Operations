export type ItemData = {
  id: string;
  name: string;
  description: string | null;
  price: number;
  photoUrl: string | null;
  soldOut: boolean;
  order: number;
};

export type CategoryData = {
  id: string;
  name: string;
  order: number;
  items: ItemData[];
};
