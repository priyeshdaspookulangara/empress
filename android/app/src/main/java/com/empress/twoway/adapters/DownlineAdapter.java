package com.empress.twoway.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.empress.twoway.R;
import com.empress.twoway.models.DownlineMember;

import java.util.List;
import java.util.Locale;

public class DownlineAdapter extends RecyclerView.Adapter<DownlineAdapter.ViewHolder> {

    private final List<DownlineMember> list;

    public DownlineAdapter(List<DownlineMember> list) {
        this.list = list;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_downline, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        DownlineMember member = list.get(position);
        holder.tvName.setText(member.getName());
        holder.tvId.setText(String.format("%s | Sponsor: %s", member.getMemberId(), member.getSponsorId()));
        holder.tvLevel.setText(String.format(Locale.US, "Level %d", member.getLevel()));
    }

    @Override
    public int getItemCount() {
        return list.size();
    }

    static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvName, tvId, tvLevel;

        ViewHolder(View itemView) {
            super(itemView);
            tvName = itemView.findViewById(R.id.tvDownlineName);
            tvId = itemView.findViewById(R.id.tvDownlineId);
            tvLevel = itemView.findViewById(R.id.tvDownlineLevel);
        }
    }
}
