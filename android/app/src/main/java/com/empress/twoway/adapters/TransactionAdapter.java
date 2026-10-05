package com.empress.twoway.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.empress.twoway.R;
import com.empress.twoway.models.Transaction;

import java.util.List;
import java.util.Locale;

public class TransactionAdapter extends RecyclerView.Adapter<TransactionAdapter.ViewHolder> {

    private final List<Transaction> list;

    public TransactionAdapter(List<Transaction> list) {
        this.list = list;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_transaction, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        Transaction tx = list.get(position);
        holder.tvType.setText(tx.getType().replace("_", " "));
        holder.tvDesc.setText(tx.getDescription() != null ? tx.getDescription() : "");
        holder.tvDate.setText(tx.getCreatedAt());

        String status = tx.getStatus();
        if ("Debit".equalsIgnoreCase(status)) {
            holder.tvAmount.setText(String.format(Locale.US, "-$%.2f", tx.getAmount()));
            holder.tvAmount.setTextColor(holder.itemView.getContext().getColor(R.color.accent_red));
        } else {
            holder.tvAmount.setText(String.format(Locale.US, "+$%.2f", tx.getAmount()));
            holder.tvAmount.setTextColor(holder.itemView.getContext().getColor(R.color.accent_green));
        }
    }

    @Override
    public int getItemCount() {
        return list.size();
    }

    static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvType, tvDesc, tvDate, tvAmount;

        ViewHolder(View itemView) {
            super(itemView);
            tvType = itemView.findViewById(R.id.tvTxType);
            tvDesc = itemView.findViewById(R.id.tvTxDesc);
            tvDate = itemView.findViewById(R.id.tvTxDate);
            tvAmount = itemView.findViewById(R.id.tvTxAmount);
        }
    }
}
