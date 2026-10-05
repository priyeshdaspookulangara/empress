package com.empress.twoway.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.empress.twoway.R;
import com.empress.twoway.models.Withdrawal;

import java.util.List;
import java.util.Locale;

public class WithdrawalAdapter extends RecyclerView.Adapter<WithdrawalAdapter.ViewHolder> {

    private final List<Withdrawal> list;

    public WithdrawalAdapter(List<Withdrawal> list) {
        this.list = list;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_withdrawal, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        Withdrawal w = list.get(position);
        holder.tvAmount.setText(String.format(Locale.US, "$%.2f USD", w.getAmount()));
        holder.tvDate.setText(w.getRequestDate());
        holder.tvStatus.setText(w.getStatus());

        if ("Approved".equalsIgnoreCase(w.getStatus()) || "Processed".equalsIgnoreCase(w.getStatus())) {
            holder.tvStatus.setTextColor(holder.itemView.getContext().getColor(R.color.accent_green));
        } else if ("Rejected".equalsIgnoreCase(w.getStatus())) {
            holder.tvStatus.setTextColor(holder.itemView.getContext().getColor(R.color.accent_red));
        } else {
            holder.tvStatus.setTextColor(holder.itemView.getContext().getColor(R.color.purple_light));
        }
    }

    @Override
    public int getItemCount() {
        return list.size();
    }

    static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvAmount, tvDate, tvStatus;

        ViewHolder(View itemView) {
            super(itemView);
            tvAmount = itemView.findViewById(R.id.tvWithdrawAmount);
            tvDate = itemView.findViewById(R.id.tvWithdrawDate);
            tvStatus = itemView.findViewById(R.id.tvWithdrawStatus);
        }
    }
}
